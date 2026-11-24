@php
    $featured = collect([
        ['deep', 'Deep Cleaning', 'SHOES', 'Pembersihan menyeluruh bagian luar dan dalam sepatu.', null],
        ['helmet-care', 'Helmet Care', 'HELMET', 'Pilih Half Face, Full Face, atau Helm Care.', 'helmet'],
        ['cap-hat', 'Cap & Hat Care', 'CAP & HAT', 'Perawatan topi mengikuti bahan dan kondisi barang.', null],
        ['backpack', 'Bag Cleaning', 'BAG', 'Pilih Clutch, Shoulder Bag, atau Backpack.', 'bag'],
    ]);
    $activeServices = $categories->flatMap->services->keyBy('code');
    $featured = $featured->filter(fn ($item) => $item[4]
        ? $categories->firstWhere('code', $item[4])?->services->isNotEmpty()
        : $activeServices->has($item[0]));
@endphp
<section class="container puro-v35-section puro-v369-services puro-v7-highlights" id="services" x-data="puroFeatured">
    <div class="puro-v35-sectionhead">
        <div><span class="puro-v35-mini">01 / OUR SERVICES</span><h2>Layanan Unggulan<span class="puro-v35-dot">.</span></h2><p>PURO menyediakan pilihan treatment yang disesuaikan untuk menjaga kebersihan dan kondisi setiap item.</p></div>
        @if ($featured->isNotEmpty())
            <div class="puro-v369-slidercontrols" aria-label="Navigasi layanan unggulan">
                <span class="puro-v369-count"><strong x-text="String(position).padStart(2, '0')">01</strong> <span>/ {{ str_pad((string) $featured->count(), 2, '0', STR_PAD_LEFT) }}</span></span>
                <button type="button" class="puro-v369-arrow" aria-label="Layanan sebelumnya" aria-controls="puro-services-track" :disabled="atStart" @click="move(-1)">←</button>
                <button type="button" class="puro-v369-arrow" aria-label="Layanan berikutnya" aria-controls="puro-services-track" :disabled="atEnd" @click="move(1)">→</button>
            </div>
        @endif
    </div>
    <div class="puro-v369-carousel">
        <div id="puro-services-track" x-ref="track" @scroll.passive="update()" @resize.window="update()" @keydown.left.self.prevent="move(-1)" @keydown.right.self.prevent="move(1)" class="puro-v35-servicegrid puro-v369-track" tabindex="0" role="region" aria-label="Layanan unggulan PURO">
            @foreach ($featured as [$code, $label, $tag, $description, $category])
                @php
                    $tariff = $activeServices->get($code)?->latestTariff;
                    $poster = $tariff && !$tariff->media_hidden ? route('poster', $tariff) : null;
                    $book = $tariff ? route('book', ['service' => $tariff->service_id]) : null;
                    $photo = $tariff ? \App\Support\Puro::serviceCover($code, $tariff) : \App\Support\Puro::photo($code);
                @endphp
                <a href="{{ $category ? route('services') . '#puro-category-' . $category : ($poster ?? $book) }}"
                    @if ($category) wire:navigate
                    @else aria-haspopup="dialog" @click.prevent="$dispatch('puro-service', @js(['name' => $tariff->display_name, 'poster' => $poster, 'book' => $book, 'price' => \App\Support\Puro::tariffPrice($tariff)]))"
                    @endif class="puro-v35-servicecard">
                    <div class="puro-v35-serviceimg">
                        @if ($photo)
                            <img src="{{ $photo }}" alt="{{ $label }}" loading="lazy" @if ($tariff?->poster) data-service-poster="1" @endif>
                        @else
                            <span class="v3610-cover-placeholder" aria-label="Foto layanan belum tersedia">PURO</span>
                        @endif
                        <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} / {{ $tag }}</span>
                    </div>
                    <div class="puro-v35-servicecontent">
                        <h3>{{ $label }}</h3><p>{{ $description }}</p>
                        <div class="puro-v35-servicebase"><span>{{ $category ? 'Lihat pilihan treatment' : \App\Support\Puro::tariffPrice($tariff) }}</span><b>↗</b></div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    <div class="v3610-all-services"><a href="{{ route('services') }}" wire:navigate class="btn btn-dark">Lihat semua layanan ↗</a></div>
</section>
