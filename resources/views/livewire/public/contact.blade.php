@php
    $wa = \App\Support\Puro::whatsapp((string) config('puro.admin_whatsapp'), 'Halo admin PURO, saya perlu bantuan.');
@endphp
<section class="puro-v35-contact" id="contact">
    <div class="container puro-v35-contactinner">
        <div>
            <span class="puro-v35-mini">06 / KONTAK</span>
            <h2>Barang kesayangan siap dirawat.<br><em>lu tinggal mulai.</em></h2>
            <p>Punya pertanyaan soal layanan atau cabang? Lihat lokasi PURO, buat pesanan, atau cek progres dengan kode yang lu punya.</p>
            <div class="puro-v35-contactactions">
                <a href="{{ route('locations') }}" wire:navigate class="btn btn-dark">Lihat Lokasi ↗</a>
                <a href="{{ route('book') }}" wire:navigate class="btn puro-v35-contactoutline">Buat Pesanan →</a>
            </div>
        </div>
        <div class="puro-v35-contactcard">
            <span>LET’S KEEP IN TOUCH</span><strong>PURO<br>SHOES CARE.</strong>
            <p>Ikuti aktivitas PURO dan hubungi kami melalui kanal berikut.</p>
            <div class="puro-social-links" aria-label="Media sosial dan kontak PURO">
                @foreach (['IG' => ['Instagram', 'https://www.instagram.com/puroshoescare/'], 'TT' => ['TikTok', 'https://www.tiktok.com/@puroshoescare']] as $initial => [$name, $url])
                    <a class="puro-social-link" href="{{ $url }}" target="_blank" rel="noopener noreferrer"><span class="puro-social-icon" aria-hidden="true">{{ $initial }}</span><span><b>{{ $name }}</b><small>@puroshoescare</small></span><i aria-hidden="true">↗</i></a>
                @endforeach
                @if ($wa)
                    <a class="puro-social-link" href="{{ $wa }}" target="_blank" rel="noopener noreferrer"><span class="puro-social-icon" aria-hidden="true">WA</span><span><b>WhatsApp</b><small>Buka WhatsApp admin</small></span><i aria-hidden="true">↗</i></a>
                @else
                    <div class="puro-social-link puro-social-placeholder"><span class="puro-social-icon" aria-hidden="true">WA</span><span><b>WhatsApp</b><small>Nomor admin belum tersedia</small></span><i aria-hidden="true">—</i></div>
                @endif
            </div>
            <a href="{{ route('track') }}" class="contact-track" @click.prevent="$dispatch('puro-track')" x-data aria-haspopup="dialog">Sudah pesan? Cek status <span>↗</span></a>
        </div>
    </div>
</section>
