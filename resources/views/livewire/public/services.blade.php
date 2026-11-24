<div class="container page-shell v3610-services-page" id="puro-services-catalogue" x-data="puroCatalogue" @hashchange.window="update()">
    <div class="page-intro puro-public-intro">
        <div><span class="eyebrow">ALL SERVICES / PURO</span><h1>Care for every<br><span class="accent-word">kind of pair.</span></h1></div>
        <p>{{ $categories->sum(fn ($category) => $category->services->count()) }} layanan PURO untuk sepatu, tas, helm, dan topi. Harga serta estimasi durasi mengikuti daftar yang tersedia; harga rentang atau yang belum ditetapkan dikonfirmasi melalui penilaian staf.</p>
    </div>
    <nav class="v3610-category-tabs" aria-label="Kategori layanan" x-ref="categories">
        @php
            $sections = ['puro-services-catalogue' => 'Semua layanan']
                + $categories->mapWithKeys(fn ($category) => ['puro-category-' . $category->code => $category->name])->all()
                + ['puro-services-faq' => 'FAQ'];
        @endphp
        @foreach ($sections as $target => $label)
            <a href="#{{ $target }}" aria-controls="{{ $target }}" @if($target === 'puro-services-faq') data-id="faq" @endif
                @class(['filter-chip', 'active' => $loop->first])
                @if ($loop->first) aria-current="location" @endif
                :class="{ active: target === @js($target) }"
                :aria-current="target === @js($target) ? 'location' : null">{{ $label }}</a>
        @endforeach
    </nav>

    @foreach ($categories as $category)
        @php
            $expressTariff = $category->services->pluck('latestTariff')->firstWhere('express_available', true);
        @endphp
        <section class="v3610-catalog-section" id="puro-category-{{ $category->code }}">
            <div class="v3610-catalog-title">
                <h2>{{ $category->name }}</h2>
                @if ($category->services->isNotEmpty())
                    <span class="pill neutral">PER {{ mb_strtoupper($category->services->first()->latestTariff->unit) }}</span>
                @endif
            </div>
            <div class="v3610-catalog-grid">
                @forelse ($category->services as $service)
                    @php
                        $tariff = $service->latestTariff;
                        $photo = \App\Support\Puro::serviceCover($service->code, $tariff);
                        $book = route('book', ['service' => $service->id]);
                        $poster = $tariff->media_hidden ? null : route('poster', $tariff);
                    @endphp
                    <a href="{{ $poster ?? $book }}" class="v3610-catalog-card" aria-haspopup="dialog" aria-label="Lihat detail {{ $tariff->display_name }}"
                        @click.prevent="$dispatch('puro-service', @js(['name' => $tariff->display_name, 'poster' => $poster, 'book' => $book, 'price' => \App\Support\Puro::tariffPrice($tariff)]))">
                        <div class="puro-v35-serviceimg">
                            @if ($photo)
                                <img src="{{ $photo }}" alt="{{ $tariff->display_name }}" loading="lazy" @if ($tariff->poster) data-service-poster="1" @endif>
                            @else
                                <span class="v3610-cover-placeholder" aria-label="Foto layanan belum tersedia">PURO</span>
                            @endif
                        </div>
                        <div class="puro-v35-servicecontent">
                            <h3>{{ $tariff->display_name }}</h3>
                            <p>{{ \App\Support\Puro::catalogueCopy($service->code, $tariff) }}</p>
                            <div class="v3610-service-meta"><span>{{ $tariff->duration_text }}</span><span>·</span><span>{{ $tariff->needsAssessment() ? 'Penilaian kondisi' : 'Harga tetap' }}</span></div>
                            <div class="v3610-service-price">{{ \App\Support\Puro::tariffPrice($tariff) }} <small>/ {{ $tariff->unit }}</small></div>
                            <p class="v3610-estimate-hint">{{ $tariff->needsAssessment() ? 'Upload foto, terima penawaran, setujui harga, lalu bayar.' : 'Pilih layanan dan lanjutkan pemesanan.' }}</p>
                            <span class="btn btn-dark btn-sm">Lihat detail ↗</span>
                        </div>
                    </a>
                @empty
                    <p class="muted">Belum ada layanan aktif di kategori ini.</p>
                @endforelse
            </div>
            @if ($expressTariff)
                <div class="v3610-one-day-note"><strong>One Day / 24 Hours · +{{ \App\Support\Puro::money($expressTariff->express_price) }} per {{ $expressTariff->unit }}</strong><br>Ketersediaan pengerjaan cepat dikonfirmasi staf sebelum harga disetujui.</div>
            @endif
        </section>
    @endforeach

    @include('livewire.public.faq')
    @include('livewire.public.service-dialog')
</div>
