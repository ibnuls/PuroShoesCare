<?php

namespace Tests\Feature;

use App\Livewire\Chatbot;
use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use App\Models\ShippingPolicy;
use App\Services\Assistant;
use App\Services\Knowledge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_rate_limit_remains_visible_to_the_chat_customer(): void
    {
        $key = 'chat:'.hash('sha256', '127.0.0.1');
        for ($i = 0; $i < 15; $i++) {
            RateLimiter::hit($key, 60);
        }
        Livewire::test(Chatbot::class)->set('question', 'Cara pesan?')->call('send')
            ->assertSet('response.reason', 'RATE_LIMIT')->assertSet('question', '')
            ->assertDispatched('chat-answer');
    }

    public function test_pdf_with_text_is_parsed_into_searchable_chunks(): void
    {
        $dir = storage_path('framework/testing/knowledge-pdf-'.uniqid());
        File::ensureDirectoryExists($dir);
        config(['puro.knowledge_directory' => $dir]);
        try {
            File::copy(base_path('tests/Fixtures/customer-guide.pdf'), $dir.'/customer-guide.pdf');
            app(Knowledge::class)->sync();
            $this->assertStringContainsString('memesan tanpa login', KnowledgeChunk::first()->body);
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_current_shipping_facts_are_sent_even_when_imported_guide_is_older(): void
    {
        $this->chunk();
        config(['puro.gemini_key' => 'fake-test']);
        ShippingPolicy::create(['code' => 'PICKUP', 'version' => 2, 'free_km' => 4, 'rate_per_km' => 7000, 'return_fee' => 0]);
        Http::fake([
            '*embedContent' => Http::response(['embedding' => ['values' => [1, 0, 0, 0, 0, 0, 0, 0]]]),
            '*generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode(['answer' => 'Untuk booking baru, kelebihan di atas 4 km dikenai Rp7.000 per km.', 'needsAdmin' => false, 'sourceIds' => [0]])]]]]]]),
        ]);
        $result = app(Assistant::class)->ask('Berapa biaya jemput saat ini?');
        $this->assertFalse($result['handoff']);
        $this->assertSame(route('book'), $result['sources'][0]['url']);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'generateContent') && str_contains($request->body(), 'Rp7.000'));
    }

    public function test_handoff_draft_includes_recent_questions_for_customer_review(): void
    {
        $r = app(Assistant::class)->ask('Saya perlu admin', [['role' => 'user', 'text' => 'Bukti pembayaran saya kurang jelas.']]);
        $this->assertTrue($r['handoff']);
        $this->assertStringContainsString('Bukti pembayaran saya kurang jelas.', $r['draft']);
        $this->assertStringContainsString('Saya perlu admin', $r['draft']);
    }

    private function chunk(): KnowledgeChunk
    {
        $d = KnowledgeDocument::create(['title' => 'Panduan PURO', 'source_path' => 'test.md', 'content_hash' => str_repeat('a', 64)]);

        return $d->chunks()->create(['position' => 0, 'heading' => 'Biaya jemput', 'body' => 'Jemput hingga 4 km gratis. Kelebihan dibulatkan ke atas, Rp5.000 per km, sekali per pesanan.', 'content_hash' => str_repeat('b', 64), 'embedding' => [1, 0, 0, 0, 0, 0, 0, 0], 'embedding_model' => config('puro.embedding_model')]);
    }

    public function test_greeting_never_calls_gemini_or_answers_old_shipping_question(): void
    {
        Http::preventStrayRequests();
        $r = app(Assistant::class)->ask('alo', [['role' => 'user', 'text' => 'Bagaimana biaya jemput?']]);
        $this->assertFalse($r['handoff']);
        $this->assertStringStartsWith('Halo!', $r['answer']);
        $this->assertStringNotContainsString('Rp5.000', $r['answer']);
        Http::assertNothingSent();
    }

    public function test_missing_key_and_explicit_admin_request_produce_whatsapp_draft_only(): void
    {
        config(['puro.admin_whatsapp' => '081234567890']);
        $r = app(Assistant::class)->ask('Admin, saya ingin menanyakan pembayaran.');
        $this->assertTrue($r['handoff']);
        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $r['whatsapp']);
        $this->assertStringContainsString('belum dikirim', $r['answer']);
        $r = app(Assistant::class)->ask('Cara pesan treatment?');
        $this->assertSame('UNAVAILABLE', $r['reason']);
    }

    public function test_retrieval_and_gemini_generation_return_only_valid_source_ids(): void
    {
        $c = $this->chunk();
        config(['puro.gemini_key' => 'fake-private-test-key']);
        Http::fake(['*embedContent' => Http::response(['embedding' => ['values' => [1, 0, 0, 0, 0, 0, 0, 0]]]), '*generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode(['answer' => 'Jarak sampai 4 km gratis. Lebihnya dibulatkan ke atas lalu dikalikan Rp5.000 per km.', 'needsAdmin' => false, 'sourceIds' => [$c->id]])]]]]]])]);
        $r = app(Assistant::class)->ask('Bagaimana biaya penjemputan?');
        $this->assertFalse($r['handoff']);
        $this->assertCount(1, $r['sources']);
        $this->assertSame('Biaya jemput', $r['sources'][0]['title']);
        Http::assertSent(fn ($r) => $r->hasHeader('x-goog-api-key', 'fake-private-test-key'));
        $this->assertStringNotContainsString('fake-private-test-key', json_encode($r));
    }

    public function test_hallucinated_citations_and_gemini_outage_fall_back(): void
    {
        $this->chunk();
        config(['puro.gemini_key' => 'fake-test']);
        Http::fake(['*embedContent' => Http::response(['embedding' => ['values' => [1, 0, 0, 0, 0, 0, 0, 0]]]), '*generateContent' => Http::response(['candidates' => [['content' => ['parts' => [['text' => json_encode(['answer' => 'Garansi 100%.', 'needsAdmin' => false, 'sourceIds' => [999999]])]]]]]])]);
        $this->assertTrue(app(Assistant::class)->ask('Bagaimana biaya jemput?')['handoff']);
        Http::swap(new Factory);
        Http::fake(['*' => Http::response([], 503)]);
        $this->assertSame('UNAVAILABLE', app(Assistant::class)->ask('Bagaimana biaya jemput?')['reason']);
    }

    public function test_no_relevant_source_never_generates_an_answer(): void
    {
        $this->chunk();
        config(['puro.gemini_key' => 'fake-test']);
        Http::fake(['*embedContent' => Http::response(['embedding' => ['values' => [0, 1, 0, 0, 0, 0, 0, 0]]])]);
        $this->assertSame('NO_SOURCE', app(Assistant::class)->ask('Bagaimana harga tiket pesawat?')['reason']);
        Http::assertSentCount(1);
    }

    public function test_document_changes_invalidate_embeddings_and_removed_files_are_inactive(): void
    {
        $dir = storage_path('framework/testing/knowledge-'.uniqid());
        File::ensureDirectoryExists($dir);
        config(['puro.knowledge_directory' => $dir]);
        try {
            File::put($dir.'/guide.md', "# Panduan PURO\n\n## Cara pesan\nIsi form pesanan lalu simpan kode tracking. Customer tidak perlu login atau membuat akun.");
            $k = app(Knowledge::class);
            $k->sync();
            $c = KnowledgeChunk::first();
            $this->assertSame('Cara pesan', $c->heading);
            $c->update(['embedding' => [1, 0, 0, 0, 0, 0, 0, 0], 'embedding_model' => config('puro.embedding_model')]);
            File::append($dir.'/guide.md', "\nBuka Cek status untuk meninjau penawaran dan pembayaran.");
            $k->sync();
            $this->assertNull(KnowledgeChunk::first()->embedding);
            File::delete($dir.'/guide.md');
            $k->sync();
            $this->assertFalse(KnowledgeDocument::first()->active);
        } finally {
            File::deleteDirectory($dir);
        }
    }

    public function test_chatbot_livewire_keeps_key_off_the_page(): void
    {
        config(['puro.gemini_key' => 'DO-NOT-EXPOSE-THIS']);
        Livewire::test(Chatbot::class)->set('question', 'halo')->call('send')->assertHasNoErrors()->assertDispatched('chat-answer')->assertDontSee('DO-NOT-EXPOSE-THIS');
    }
}
