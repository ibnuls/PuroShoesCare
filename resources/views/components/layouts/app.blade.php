<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111111">
    <meta name="description"
        content="PURO: perawatan sepatu, tas, helm dan topi. Pesan, setujui harga, bayar dan pantau perawatan dalam satu tempat.">
    <title>{{ $title ?? 'PURO · Pure Care Pure Solution' }}</title>
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/puro-v3610.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/responsive-v9.css') }}">@vite(['resources/css/app.css', 'resources/js/app.js'])@livewireStyles
</head>

<body>
    <a href="#main-content" class="skip-link">Langsung ke konten</a>
    <header class="site-header" x-data="{ open: false }" @keydown.escape.window="open=false">
        <div class="container header-inner"><a href="{{ route('home') }}" wire:navigate class="brand"
                aria-label="PURO beranda"><img src="{{ asset('assets/puro-logo-yellow.svg') }}" width="120"
                    height="44" alt="PURO Shoes Care"></a>
            <nav class="public-nav" :class="{ 'mobile-open': open }" aria-label="Navigasi utama" @click="open=false">
                @foreach (['home' => 'Beranda', 'services' => 'Layanan', 'locations' => 'Lokasi', 'book' => 'Form', 'about' => 'Tentang', 'contact' => 'Kontak'] as $route => $label)
                    <a href="{{ route($route) }}" wire:navigate
                        class="{{ request()->routeIs($route) ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="header-actions"><a class="text-button track-link" href="{{ route('track') }}"
                    wire:navigate><span class="tiny-dot"></span>Cek status</a><a class="header-cta"
                    href="{{ route('book') }}" wire:navigate>Pesan treatment ↗</a><button class="hamburger"
                    @click="open=!open" :aria-expanded="open"
                    aria-label="Buka menu"><span></span><span></span><span></span></button></div>
        </div>
    </header>
    @auth
        <div class="staff-bar">
            <div class="container"><a href="{{ route('staff.dashboard') }}" wire:navigate>Dashboard
                    ↗</a><span>{{ auth()->user()->full_name }} · {{ auth()->user()->role->name }}</span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>
            </div>
        </div>
    @endauth
    <main id="main-content">{{ $slot }}</main>
    <footer class="site-footer">
        <div class="container footer-main">
            <div><a href="{{ route('home') }}" wire:navigate class="footer-brand"><img
                        src="{{ asset('assets/puro-logo-yellow.svg') }}" width="128" height="48"
                        alt="PURO"></a>
                <p>Pure Care. Pure Solution.<br>Perawatan untuk setiap barang kesayangan.</p>
            </div>
            <div><span class="footer-label">CUSTOMER</span><a href="{{ route('services') }}" wire:navigate>Layanan &
                    FAQ</a><a href="{{ route('guide') }}" wire:navigate>Panduan pemesanan</a><a
                    href="{{ route('track') }}" wire:navigate>Cek status pesanan</a></div>
            <div><span class="footer-label">PURO</span><a href="{{ route('locations') }}" wire:navigate>Lokasi
                    cabang</a><a href="{{ route('contact') }}" wire:navigate>Kontak admin</a><a
                    href="{{ route('login') }}" wire:navigate class="staff-entry">Masuk staf ↗</a></div>
        </div>
        <div class="container footer-bottom">© {{ now()->year }} PURO · Shoes, Bags, Helmets, Cap & Hat.</div>
    </footer>
    @persist('puro-assistant')
        <livewire:chatbot />
    @endpersist
    @livewireScripts
</body>

</html>
