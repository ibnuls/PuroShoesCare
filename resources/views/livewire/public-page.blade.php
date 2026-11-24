<div>
    @switch($page)
        @case('home') @include('livewire.public.home') @break
        @case('services') @include('livewire.public.services') @break
        @case('locations') @include('livewire.public.locations') @break
        @case('about') @include('livewire.public.about') @break
        @case('contact') @include('livewire.public.contact') @break
        @case('guide')
        <div class="container page-shell guide-shell">
            <div class="page-intro">
                <div><span class="eyebrow">CUSTOMER GUIDE</span>
                    <h1>Pesan dengan <em class="accent-word">mudah.</em></h1>
                </div>
            </div>
            <p class="lead">Panduan layanan, penilaian kondisi, pembayaran, pengiriman, dan bantuan customer.</p>
            <div class="notice">Jawaban panduan bersifat umum. Keputusan harga dan transaksi mengikuti penawaran serta
                verifikasi admin pada pesananmu.</div>
            @forelse($documents as $document)
                <section class="guide-document" id="document-{{ $document->id }}">
                    <h2>{{ ucfirst($document->title) }}</h2>
                    @foreach ($document->chunks as $chunk)
                        <article id="chunk-{{ $chunk->id }}" class="guide-chunk">
                            <h3>{{ $chunk->heading }}</h3>
                            <div class="plain-text">{{ $chunk->body }}</div>
                        </article>
                    @endforeach
                </section>
            @empty<p>Dokumen panduan sedang disiapkan admin. Hubungi admin melalui menu Kontak.</p>
            @endforelse
        </div>
            @break
    @endswitch
</div>
