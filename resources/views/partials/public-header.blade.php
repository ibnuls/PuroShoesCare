<header class="site-header" id="header" x-data="{ open: false, section: location.hash }" @hashchange.window="section = location.hash" @keydown.escape.window="open = false">
    <div class="header-inner container">
        <a class="brand" href="{{ route('home') }}" wire:navigate aria-label="PURO Shoes Care ke beranda">
            <span class="brand-icon"><img src="{{ asset('assets/puro-mark-black.svg') }}" alt="" width="37" height="37"></span>
            <span class="brand-word">PURO<small>SHOES CARE</small></span>
        </a>
        <nav class="public-nav" id="public-nav" :class="{ open }" aria-label="Navigasi publik" @click="open = false">
            @foreach (['home' => 'Beranda', 'services' => 'Layanan', 'locations' => 'Lokasi', 'book' => 'Form', 'about' => 'Tentang', 'contact' => 'Kontak'] as $route => $label)
                @php
                    $anchor = in_array($route, ['about', 'contact'], true);
                    $isHomeSection = request()->routeIs('home') && ($route === 'home' || $anchor);
                    $url = $anchor ? route('home') . '#' . $route : route($route);
                    $selected = $route === 'book' ? request()->routeIs('book', 'book.*') : request()->routeIs($route);
                    $activeHash = $anchor ? '#' . $route : '';
                    $activeSection = $anchor ? 'section === $el.dataset.homeAnchor' : "!['#about', '#contact'].includes(section)";
                @endphp
                <a href="{{ $url }}" @unless($anchor && request()->routeIs('home')) wire:navigate @endunless
                    @class(['active' => $selected]) @if($selected) aria-current="page" @endif
                    @if ($isHomeSection)
                        data-home-anchor="{{ $activeHash }}"
                        :class="{ active: {{ $activeSection }} }"
                        :aria-current="({{ $activeSection }}) ? 'page' : null"
                    @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="header-actions">
            <a class="text-button track-link" href="{{ route('track') }}" @click.prevent="$dispatch('puro-track')" aria-haspopup="dialog" @if(request()->routeIs('track')) aria-current="page" @endif><span class="tiny-dot"></span> Cek status</a>
            <a class="header-cta" href="{{ route('book') }}" wire:navigate>Pesan treatment <span>↗</span></a>
            <button class="hamburger" id="mobile-toggle" type="button" aria-controls="public-nav" :aria-label="open ? 'Tutup menu' : 'Buka menu'" :aria-expanded="open" @click="open = !open"><span></span><span></span></button>
        </div>
    </div>
</header>
