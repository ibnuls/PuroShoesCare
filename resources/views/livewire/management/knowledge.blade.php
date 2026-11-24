<div class="dashboard-intro"><div><span class="eyebrow">SUPER ADMIN / KNOWLEDGE</span><h1>Dokumen Chatbot</h1><p>Panduan yang dipakai Asisten PURO.</p></div></div>
@include('partials.feedback')
        <div class="management-layout">
            <section class="panel panel-pad">
                <h2>Sumber jawaban customer</h2>
                <p>Dokumen aktif dipakai chatbot dan tersedia di panduan customer. Unggah informasi layanan yang boleh
                    dibaca customer.</p>
                @foreach ($rows as $doc)
                    <article class="management-row">
                        <div><strong>{{ ucfirst($doc->title) }}</strong>
                            <p>{{ $doc->chunks_count }} chunk · {{ $doc->indexed_count }} embedding siap</p><span
                                class="pill {{ $doc->active ? 'green' : 'neutral' }}">{{ $doc->active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div><button class="btn btn-ghost btn-sm"
                            wire:click="toggleDocument({{ $doc->id }})">{{ $doc->active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </article>
                @endforeach
                <div class="notice">
                    Model jawaban: {{ config('puro.chat_model') }}<br>Model embedding:
                    {{ config('puro.embedding_model') }}<br>API key:
                    {{ config('puro.gemini_key') ? 'Terisi di server' : 'Belum diatur di .env' }}</div>
            </section>
            <section class="panel panel-pad">
                <h2>Perbarui panduan</h2>
                <form wire:submit="uploadKnowledge"><label>Dokumen MD, TXT atau PDF teks (maks. 10 MB)<input
                            type="file" class="field" wire:model="file" accept=".md,.txt,.pdf" required></label>
                    <p class="small muted">PDF scan perlu di-OCR dahulu. Dokumen dipecah menjadi potongan informasi
                        yang dapat dicari.</p><button class="btn btn-lime" type="submit"
                        wire:loading.attr="disabled">Unggah & baca dokumen</button>
                </form>
                <div class="rule form-subhead"></div>
                <h3>Indeks RAG</h3>
                <p>Isi GEMINI_API_KEY di .env, lalu buat embedding agar pertanyaan dapat dicocokkan dengan panduan.
                    Proses ini memakai API Gemini.</p>
                <div class="inline-actions"><button class="btn btn-ghost" wire:click="sync(false)"
                        wire:loading.attr="disabled">Baca ulang folder</button><button class="btn btn-dark"
                        wire:click="sync(true)" wire:loading.attr="disabled">Perbarui embedding ↗</button></div>
                <p class="small muted">Untuk dokumen besar gunakan terminal: php artisan puro:knowledge-sync --embed
                </p>
                @if ($indexMessage)
                    <div class="notice success">{{ $indexMessage }}</div>
                @endif
                <a href="{{ route('guide') }}" wire:navigate class="inline-link">Tinjau panduan customer ↗</a>
            </section>
        </div>

