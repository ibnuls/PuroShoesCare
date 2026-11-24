<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#111111">
    <meta name="description" content="PURO · Perawatan sepatu, tas, helm, dan topi. Pesan dan pantau perawatan dalam satu tempat.">
    <title>{{ $title ?? 'PURO · Pure Care Pure Solution' }}</title>
    <link rel="icon" href="{{ asset('assets/favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('assets/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/puro-v3610.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/payments-v8.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/responsive-v9.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/assistant.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body @class(['puro-home' => request()->routeIs('home')])>
    @php
        $staffPage = request()->routeIs('staff.dashboard', 'staff.order', 'staff.manage');
    @endphp
    <a href="#{{ $staffPage ? 'main-content' : 'app' }}" class="skip-link">Langsung ke konten</a>
    @if ($staffPage)
        @include('partials.staff-shell', ['content' => $slot])
    @else
        @include('partials.public-header')
        <main id="app">{{ $slot }}</main>
        @include('partials.public-footer')
        @include('partials.tracking-dialog')
        @unless(request()->routeIs('login'))
            @persist('puro-assistant')
                <livewire:chatbot />
            @endpersist
        @endunless
    @endif
    @livewireScripts
</body>
</html>
