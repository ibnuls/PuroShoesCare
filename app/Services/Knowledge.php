<?php

namespace App\Services;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;

class Knowledge
{
    public function embedding(string $text, string $task): array
    {
        $model = config('puro.embedding_model');
        $key = config('puro.gemini_key');
        if (! $key) {
            throw new \RuntimeException('GEMINI_API_KEY belum diisi.');
        }
        $response = Http::withHeaders(['x-goog-api-key' => $key])->timeout(30)->connectTimeout(8)->retry(2, 300, throw: false)->post('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':embedContent', ['model' => 'models/'.$model, 'content' => ['parts' => [['text' => $text]]], 'taskType' => $task, 'outputDimensionality' => 768]);
        if (! $response->successful()) {
            throw new \RuntimeException('Layanan embedding belum tersedia.');
        }
        $v = $response->json('embedding.values');
        if (! is_array($v) || count($v) < 8 || ! collect($v)->every(fn ($n) => is_numeric($n) && is_finite((float) $n))) {
            throw new \RuntimeException('Respons embedding tidak valid.');
        }

        return array_map('floatval', $v);
    }

    public function split(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $heading = 'Panduan pelanggan';
        $out = [];
        $buffer = '';
        $flush = function () use (&$buffer, &$out, &$heading) {
            $b = trim($buffer);
            while (mb_strlen($b) > 1200) {
                $part = mb_substr($b, 0, 1100);
                $cut = mb_strrpos($part, ' ');
                if ($cut !== false && $cut > 800) {
                    $part = mb_substr($part, 0, $cut);
                }
                $out[] = ['heading' => $heading, 'body' => $part];
                $b = mb_substr($b, max(1, mb_strlen($part) - 120));
            }
            if (mb_strlen($b) > 25) {
                $out[] = ['heading' => $heading, 'body' => $b];
            }
            $buffer = '';
        };
        foreach (explode("\n", $text) as $line) {
            if (preg_match('/^#{1,4}\s+(.+)/u', $line, $m)) {
                $flush();
                $heading = trim($m[1]);
            } else {
                $buffer .= $line."\n";
            }
        }
        $flush();

        return $out;
    }

    public function sync(bool $embed = false): array
    {
        $dir = config('puro.knowledge_directory');
        File::ensureDirectoryExists($dir);
        $count = 0;
        $indexed = 0;
        $seen = [];
        foreach (File::files($dir) as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['md', 'txt', 'pdf'])) {
                continue;
            }
            if ($file->getSize() > 10 * 1024 * 1024) {
                throw new \RuntimeException('Dokumen melebihi 10 MB.');
            }
            $path = $file->getFilename();
            $seen[] = $path;
            $bytes = File::get($file->getPathname());
            $hash = hash('sha256', $bytes);
            $doc = KnowledgeDocument::where('source_path', $path)->first();
            if (! $doc || $doc->content_hash !== $hash) {
                $text = $ext === 'pdf' ? (new Parser)->parseContent($bytes)->getText() : $bytes;
                $parts = $this->split($text);
                if (! $parts) {
                    throw new \RuntimeException('Dokumen kosong/tidak memiliki teks; lakukan OCR untuk PDF hasil scan.');
                }
                $doc = DB::transaction(function () use ($doc, $path, $hash, $parts) {
                    $d = $doc ?? new KnowledgeDocument;
                    $d->fill(['title' => preg_replace('/[_.-]+/', ' ', pathinfo($path, PATHINFO_FILENAME)), 'source_path' => $path, 'content_hash' => $hash, 'status' => 'READY'])->save();
                    $d->chunks()->delete();
                    foreach ($parts as $i => $part) {
                        $d->chunks()->create($part + ['position' => $i, 'content_hash' => hash('sha256', $part['body'])]);
                    }

                    return $d;
                });
            }
            $count++;
            if ($embed && $doc->active) {
                foreach ($doc->chunks()->where(fn ($q) => $q->whereNull('embedding')->orWhere('embedding_model', '<>', config('puro.embedding_model')))->get() as $chunk) {
                    $vector = $this->embedding($chunk->heading."\n".$chunk->body, 'RETRIEVAL_DOCUMENT');
                    $chunk->update(['embedding' => $vector, 'embedding_model' => config('puro.embedding_model')]);
                    $indexed++;
                }
            }
        }
        // A removed file must stop being retrieved. Explicitly disabled docs stay disabled on re-sync.
        KnowledgeDocument::whereNotIn('source_path', $seen)->update(['active' => false]);

        return ['documents' => $count, 'embedded' => $indexed];
    }

    public static function cosine(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            return 0;
        }
        $dot = $x = $y = 0;
        foreach ($a as $i => $v) {
            $dot += $v * $b[$i];
            $x += $v * $v;
            $y += $b[$i] * $b[$i];
        }

        return $x > 0 && $y > 0 ? $dot / sqrt($x * $y) : 0;
    }

    public function retrieve(string $query): array
    {
        $v = $this->embedding($query, 'RETRIEVAL_QUERY');

        return KnowledgeChunk::with('document')->whereHas('document', fn ($q) => $q->where('active', true))->where('embedding_model', config('puro.embedding_model'))->whereNotNull('embedding')->get()->map(fn ($c) => ['chunk' => $c, 'score' => self::cosine($v, $c->embedding)])->filter(fn ($v) => $v['score'] >= config('puro.rag_min_similarity'))->sortByDesc('score')->take(config('puro.rag_top_k'))->values()->all();
    }
}
