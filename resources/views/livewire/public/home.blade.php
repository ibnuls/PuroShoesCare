<section class="hero-v35" aria-label="PURO Shoes Care">
    <div class="container hero-v35-frame">
        <div class="hero-v35-copy">
            <span class="hero-v35-kicker"><span class="hero-v35-led"></span> PROFESSIONAL CARE SERVICE</span>
            <h1>Professional<br>Care for Every<br><em>Essential.</em></h1>
            <p>Perawatan profesional untuk sepatu, helm, tas, dan topi. PURO menghadirkan layanan dengan perhatian pada setiap detail, didukung proses pengerjaan yang terukur dan transparan.</p>
            <div class="hero-v35-buttons">
                <a href="{{ route('book') }}" wire:navigate class="btn hero-v35-book">Buat Pesanan <img src="{{ asset('assets/icon-bag.png') }}" class="hero-v362-icon" alt=""></a>
                <a href="{{ route('services') }}" wire:navigate class="btn hero-v35-outline">Lihat Layanan <img src="{{ asset('assets/icon-loupe.png') }}" class="hero-v362-icon hero-v362-loupe" alt=""></a>
            </div>
            <span class="hero-v35-smallnote">SHOES / BAGS / HELMETS / CAP & HAT</span>
        </div>
        <div class="hero-v35-media">
            <div class="hero-v35-halo"></div>
            <picture class="hero-v35-picture"><img src="{{ asset('assets/hero-puro-v5.jpg') }}" alt="Foto sepatu pilihan PURO Shoes Care" class="hero-v35-photo" fetchpriority="high"></picture>
            <span class="hero-v35-side-text">A NEW STANDARD OF CARE</span>
        </div>
    </div>
    <div class="container hero-v35-perks">
        @foreach (['✧' => ['Premium Treatment', 'Treatment sesuai kondisi'], '✳' => ['Teknisi Berpengalaman', 'Pengerjaan terdokumentasi'], '↗' => ['Pickup & Delivery', 'Jemput dan antar praktis'], '◎' => ['Aman & Terpantau', 'Cek status lewat kode']] as $icon => [$label, $detail])
            <div><span class="hero-v35-perkicon">{{ $icon }}</span><span><strong>{{ $label }}</strong><small>{{ $detail }}</small></span></div>
        @endforeach
    </div>
</section>

@include('livewire.public.featured')
@include('livewire.public.promotions')

<section class="container puro-v35-section puro-v35-branchsection" id="branch-preview">
    <div class="puro-v35-sectionhead">
        <div><span class="puro-v35-mini">03 / OUR LOCATIONS</span><h2>Pilih Cabang Terdekat<span class="puro-v35-dot">.</span></h2><p>Temukan lokasi layanan PURO dan pilih titik layanan yang sesuai dengan kebutuhan lu.</p></div>
        <a class="puro-v35-textlink" href="{{ route('locations') }}" wire:navigate>Lihat semua cabang <span>↗</span></a>
    </div>
    <div class="puro-v35-branchgrid">
        @forelse ($branches as $branch)
            <article class="puro-v35-branchcard">
                <div class="puro-v35-branchphoto">
                    @if ($image = \App\Support\Puro::branchCover($branch))
                        <img src="{{ $image }}" alt="Foto {{ $branch->name }}" loading="lazy">
                    @else
                        <span class="v3610-cover-placeholder" aria-label="Foto cabang belum tersedia">PURO</span>
                    @endif
                    <span>STORE {{ str_pad((string) $branch->id, 2, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="puro-v35-branchcontent">
                    <h3>{{ $branch->name }}</h3><p>{{ $branch->area }}</p>
                    <span class="puro-v35-open active-text"><i></i> {{ config('puro.demo') ? 'Lokasi demo · informasi dapat berubah' : 'Cabang aktif' }}</span>
                    <a href="{{ route('locations') }}#cabang-{{ $branch->id }}" wire:navigate class="puro-v35-branchlink">Lihat Detail <span>→</span></a>
                </div>
            </article>
        @empty
            <p>Belum ada cabang yang menerima pesanan.</p>
        @endforelse
    </div>
</section>

<section class="puro-v35-process">
    <div class="container">
        <div class="puro-v35-sectionhead"><div><span class="puro-v35-mini">04 / HOW IT WORKS</span><h2>Empat langkah. <span class="puro-v35-lime">Lebih gampang.</span></h2><p>Booking tanpa login. Simpan kode tracking buat memantau barang lu.</p></div></div>
        <div class="puro-v35-processgrid">
            @foreach ([
                'Pilih layanan' => 'Isi form. Upload foto kondisi untuk layanan dengan penilaian harga.',
                'Setujui harga' => 'Harga tetap langsung checkout. Harga rentang menunggu penawaran staf.',
                'Bayar via QRIS' => 'Bayar sesuai harga yang disetujui dan tunggu verifikasi Admin Cabang.',
                'Pantau progres' => 'Simpan kode pesanan sejak booking dibuat untuk memantau penawaran dan pengerjaan.',
            ] as $label => $detail)
                <article><span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $label }}</h3><p>{{ $detail }}</p></article>
            @endforeach
        </div>
        <div class="puro-v35-tracking">
            <div><span class="puro-v35-mini">CHECK YOUR ORDER</span><h3>Barang lu udah sampai mana?</h3><p>Masukkan kode pesanan. Nggak butuh email atau akun customer.</p></div>
            <form action="{{ route('track') }}" method="GET" class="track-lookup">
                <input class="field" aria-label="Kode pesanan" name="code" placeholder="Masukkan kode pesanan" maxlength="40" required>
                <button class="btn btn-lime" type="submit">Lacak Status ↗</button>
            </form>
        </div>
    </div>
</section>

@include('livewire.public.about')
@include('livewire.public.contact')
@include('livewire.public.service-dialog')
