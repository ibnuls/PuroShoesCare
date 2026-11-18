<div>
    @if ($page === 'home')
        <section class="hero-v35">
            <div class="container hero-v35-frame">
                <div class="hero-v35-copy"><span class="hero-v35-kicker"><span class="hero-v35-led"></span> PROFESSIONAL
                        CARE SERVICE</span>
                    <h1>Professional<br>Care for Every<br><em>Essential.</em></h1>
                    <p>Perawatan profesional untuk sepatu, helm, tas, dan topi. PURO menghadirkan layanan dengan
                        perhatian pada setiap detail, didukung proses pengerjaan yang terukur dan transparan.</p>
                    <div class="hero-v35-buttons"><a href="{{ route('book') }}" wire:navigate
                            class="btn hero-v35-book">Buat Pesanan ↗</a><a href="{{ route('services') }}" wire:navigate
                            class="btn hero-v35-outline">Lihat Layanan ⌕</a></div><span class="hero-v35-smallnote">SHOES
                        / BAGS / HELMETS / CAP & HAT</span>
                </div>
                <div class="hero-v35-media">
                    <div class="hero-v35-halo"></div>
                    <picture class="hero-v35-picture"><img src="{{ asset('assets/hero-puro-v5.jpg') }}"
                            alt="Sepatu pilihan PURO" class="hero-v35-photo" fetchpriority="high"></picture><span
                        class="hero-v35-side-text">A NEW STANDARD OF CARE</span>
                </div>
            </div>
            <div class="container hero-v35-perks">
                @foreach (['✧' => ['Premium Treatment', 'Treatment sesuai kondisi'], '✳' => ['Teknisi Berpengalaman', 'Pengerjaan terdokumentasi'], '↗' => ['Pickup & Delivery', 'Jemput dan antar praktis'], '◎' => ['Aman & Terpantau', 'Cek status lewat kode']] as $icon => $perk)
                    <div><span
                            class="hero-v35-perkicon">{{ $icon }}</span><span><strong>{{ $perk[0] }}</strong><small>{{ $perk[1] }}</small></span>
                    </div>
                @endforeach
            </div>
        </section>
        <section class="container section-pad">
            <div class="section-heading">
                <div><span class="eyebrow">01 / CARE MENU</span>
                    <h2>Good care.<br><em class="accent-word">Better essentials.</em></h2>
                </div>
                <p>Sepatu, tas, helm, dan topi: satu alur perawatan yang bisa kamu pantau.</p><a class="btn btn-dark"
                    href="{{ route('services') }}" wire:navigate>Semua layanan ↗</a>
            </div>
            <div class="category-grid">
                @foreach ($categories as $category)
                    <a href="{{ route('services') }}#{{ $category->code }}" wire:navigate class="category-tile"><span
                            class="eyebrow">0{{ $loop->iteration }} / TREATMENT</span>
                        <h3>{{ $category->name }}</h3><span>{{ $category->services->count() }} layanan ↗</span>
                    </a>
                @endforeach
            </div>
        </section>
        @if ($promotions->isNotEmpty())
            <section class="container section-pad">
                <div class="section-heading">
                    <div><span class="eyebrow">EXTRA CARE, LESS PRICE</span>
                        <h2>Care deals.</h2>
                    </div>
                    <p>Potongan mengikuti batas promo dan tarif pesanan.</p>
                </div>
                <div class="promo-grid">
                    @foreach ($promotions as $p)
                        <article class="deal-card">
                            @if ($p->cover)
                                <img src="{{ $p->cover->url }}" alt="Promo {{ $p->code }}" loading="lazy">
                            @endif
                            <span class="eyebrow">
                                {{ $p->branch?->name ?? 'SEMUA CABANG' }}</span>
                            <h3>Hemat {{ $p->latestVersion->percent }}%</h3>
                            <p>Maks. {{ \App\Support\Puro::money($p->latestVersion->cap_amount) }}</p>
                            <code>{{ $p->code }}</code><a href="{{ route('book') }}" wire:navigate
                                class="btn btn-dark">Pesan treatment ↗</a>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
        <section class="how-section">
            <div class="container"><span class="eyebrow">02 / HOW IT WORKS</span>
                <h2>Easy steps.<br><em class="accent-word">Clean results.</em></h2>
                <div class="steps-grid">
                    @foreach (['Pilih treatment' => 'Isi detail barang, cabang, dan metode penyerahan.', 'Setujui harga' => 'Staf menilai kondisi jika layanan membutuhkan penawaran.', 'Bayar & pantau' => 'Kirim bukti, tunggu verifikasi admin, lalu pantau perawatan.'] as $title => $text)
                        <article><span class="step-num">0{{ $loop->iteration }}</span>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </article>
                    @endforeach
                </div>
                <a href="{{ route('guide') }}" wire:navigate class="btn btn-dark">Panduan lengkap ↗</a>
            </div>
        </section>
        <section class="container section-pad">
            <div class="section-heading">
                <div><span class="eyebrow">03 / FIND YOUR PURO</span>
                    <h2>Closer to you.</h2>
                </div><a class="btn btn-ghost" href="{{ route('locations') }}" wire:navigate>Lihat
                    {{ $branches->count() }} cabang ↗</a>
            </div>@include('partials.branch-cards', ['limit' => 2])
        </section>
    @elseif($page === 'services')
        <div class="container page-shell">
            <div class="page-intro">
                <div><span class="eyebrow">OUR CARE MENU</span>
                    <h1>Every essential.<br><em class="accent-word">Every detail.</em></h1>
                </div>
                <p>Harga ditampilkan per barang. Treatment dengan penilaian baru dikerjakan setelah harga dan pembayaran
                    disetujui.</p>
            </div>
            <nav class="category-nav">
                @foreach ($categories as $category)
                    <a href="#{{ $category->code }}">{{ $category->name }}</a>
                @endforeach
                <a href="#faq">
                    FAQ ↗</a>
            </nav>
            @foreach ($categories as $category)
                <section class="catalog-section" id="{{ $category->code }}">
                    <div class="section-heading">
                        <h2>{{ $category->name }}</h2><span class="eyebrow">{{ $category->services->count() }}
                            TREATMENTS</span>
                    </div>
                    <div class="catalog-grid">
                        @foreach ($category->services as $s)
                            @php($t = $s->latestTariff)@if ($t)
                                <article class="service-card"><img class="service-cover"
                                        src="{{ $t->cover?->url ?? \App\Support\Puro::photo($s->code) }}"
                                        alt="{{ $t->display_name }}" loading="lazy">
                                    <div class="service-content"><span class="eyebrow">{{ $t->duration_text }}</span>
                                        <h3>{{ $t->display_name }}</h3>
                                        <p class="service-price">
                                            @if ($t->pricing_mode === 'QUOTE')
                                                Penilaian admin @else{{ \App\Support\Puro::money($t->min_price) }}
                                                @if ($t->pricing_mode === 'RANGE')
                                                    – {{ \App\Support\Puro::money($t->max_price) }}
                                                @endif
                                                <small>/ {{ $t->unit }}</small>
                                            @endif
                                        </p>
                                        <p>{{ $t->description }}</p>
                                        <details>
                                            <summary>Detail treatment +</summary>
                                            <p>{{ $t->customer_note }}</p>
                                            <p>{{ $t->express_available ? 'One Day tersedia dengan konfirmasi staf, tambahan Rp25.000 per barang.' : 'Pengerjaan mengikuti estimasi dan penilaian kondisi.' }}
                                            </p><a class="inline-link" href="{{ route('poster', $t) }}" target="_blank"
                                                rel="noopener">Lihat poster tarif v{{ $t->version }} ↗</a>
                                        </details><a href="{{ route('book', ['service' => $s->id]) }}" wire:navigate
                                            class="btn btn-dark btn-wide">Buat pesanan ↗</a>
                                    </div>
                                </article>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach
            <section id="faq" class="faq-section"><span class="eyebrow">FREQUENTLY ASKED QUESTIONS</span>
                <h2>Yang sering ditanyakan.</h2>
                @foreach ([
        'Apakah harus membuat akun?' => 'Tidak. Isi form dan simpan kode pesanan yang muncul sejak booking. Kode digunakan untuk membuka status dari perangkat lain.',
        'Bagaimana cara menghitung biaya jemput?' => 'Penjemputan sampai 4 km gratis. Kelebihannya dibulatkan ke atas lalu dikalikan tarif per km yang berlaku saat booking. Biaya dihitung satu kali per pesanan; pengembalian gratis.',
        'Kapan saya perlu membayar?' => 'Untuk harga tetap, tagihan tersedia setelah booking. Untuk layanan dengan penilaian, tunggu penawaran admin dan setujui dahulu.',
        'Jika bukti pembayaran ditolak, apakah pesanan batal?' => 'Pesanan tetap aktif. Baca alasan admin dan kirim ulang bukti pada halaman status. Pembatalan pesanan adalah tindakan terpisah. Jangan transfer ulang sebelum konfirmasi transaksi sebelumnya.',
        'Bagaimana jika halaman tertutup?' => 'Buka Cek status dan Pesanan terakhir di browser yang sama, atau masukkan kode yang sudah disimpan. Penyimpanan browser bisa hilang jika data browser dihapus.',
        'Bagaimana jika chatbot tidak bisa membantu?' => 'Pilih bantuan admin. Ringkasan dapat dibuka di WhatsApp untuk ditinjau lalu dikirim sendiri.',
    ] as $q => $a)
                    <details>
                        <summary>{{ $q }}</summary>
                        <p>{{ $a }}</p>
                    </details>
                @endforeach
                <a href="{{ route('guide') }}" wire:navigate class="btn btn-lime">
                    Baca panduan customer ↗</a>
            </section>
        </div>
    @elseif($page === 'locations')
        <div class="container page-shell">
            <div class="page-intro">
                <div><span class="eyebrow">OUR LOCATIONS</span>
                    <h1>Find your <em class="accent-word">PURO.</em></h1>
                </div>
                <p>Pilih cabang terdekat untuk penilaian, penyerahan, dan perawatan barang.</p>
            </div>
            @if (config('puro.demo'))
                <div class="notice warn">Mode demo: alamat cabang masih contoh. Pastikan alamat operasional melalui
                    admin sebelum datang.</div>
            @endif @include('partials.branch-cards', ['limit' => 100])
        </div>
    @elseif($page === 'guide')
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
    @elseif($page === 'about')
        <div class="container page-shell">
            <div class="page-intro">
                <div><span class="eyebrow">PURE CARE. PURE SOLUTION.</span>
                    <h1>Care beyond <em class="accent-word">shoes.</em></h1>
                </div>
            </div>
            <div class="split-layout"><img class="about-photo" src="{{ asset('assets/hero-puro-sneaker.jpg') }}"
                    alt="Perawatan sepatu PURO">
                <article class="panel panel-pad">
                    <h2>Every essential deserves care.</h2>
                    <p class="lead">PURO merawat sepatu, tas, helm, dan topi dengan penanganan sesuai bahan dan
                        kondisi.</p>
                    <p>Alur pemesanan mencakup penilaian kondisi, persetujuan harga, verifikasi pembayaran, pengerjaan
                        teknisi, dan quality checking. Setiap tahap dapat dilihat melalui kode pesanan.</p><a
                        href="{{ route('services') }}" wire:navigate class="btn btn-lime">Jelajahi layanan ↗</a>
                </article>
            </div>
        </div>
    @elseif($page === 'contact')
        <div class="container page-shell">
            <div class="page-intro">
                <div><span class="eyebrow">LET’S TALK</span>
                    <h1>Need a <em class="accent-word">hand?</em></h1>
                </div>
            </div>
            <div class="split-layout">
                <article class="panel panel-pad">
                    <h2>Bantuan admin PURO</h2>
                    <p>Untuk penilaian khusus, kendala pembayaran, perubahan pesanan, atau keluhan, hubungi admin.
                        Sertakan kode pesanan jika sudah booking.</p>@php($wa = \App\Support\Puro::whatsapp(config('puro.admin_whatsapp'), 'Halo admin PURO, saya perlu bantuan.'))@if ($wa)
                        <a class="btn btn-lime" href="{{ $wa }}" target="_blank"
                            rel="noopener noreferrer">Buka WhatsApp admin ↗</a>
                    <p class="muted small">Tinjau pesan dan tekan Kirim di WhatsApp.</p>@else<div
                            class="notice warn">Nomor WhatsApp admin belum diatur. Hubungi cabang secara langsung.
                        </div>
                    @endif
                </article>
                <article class="panel panel-pad">
                    <h2>Sudah membuat pesanan?</h2>
                    <p>Buka status menggunakan kode pesanan. Kamu bisa membaca penawaran, mengirim bukti, dan melihat
                        progres tanpa login.</p><a href="{{ route('track') }}" wire:navigate class="btn btn-dark">Cek
                        status ↗</a><a href="{{ route('guide') }}" wire:navigate class="inline-link">Panduan customer
                        ↗</a>
                </article>
            </div>
        </div>
    @endif
</div>
