@php
    $actor = auth()->user();
    $isSuper = $actor->isRole('superadmin');
    $staffLabels = [
        'catalog' => 'Konten Beranda',
        'branches' => 'Manajemen Cabang',
        'qris' => 'Grup QRIS',
        'accounts' => $isSuper ? 'Akun Admin' : 'Tim Cabang',
        'permissions' => 'Role & Permission',
        'promos' => $isSuper ? 'Promo Global' : 'Promo Cabang',
        'reports' => 'Analitik Penjualan',
        'knowledge' => 'Dokumen Chatbot',
    ];
    $activeTab = request()->route('tab');
@endphp
<div class="staff-app" x-data="{ staffMenu: false }" :class="{ 'staff-menu-open': staffMenu }" @keydown.escape.window="staffMenu = false">
    <aside class="staff-sidebar">
        <div class="sidebar-brand">
            <a href="{{ route('staff.dashboard') }}" wire:navigate aria-label="Dashboard PURO"><img class="sidebar-logo" src="{{ asset('assets/puro-logo-yellow.svg') }}" alt="PURO Shoes Care"></a>
            <span class="sidebar-brand-caption">OPERATIONS CENTER</span>
            <button class="staff-mobile-toggle" type="button" aria-label="Menu dashboard staf" :aria-expanded="staffMenu" aria-controls="staff-nav" @click="staffMenu = !staffMenu"><span></span><span></span></button>
        </div>
        <div class="sidebar-divider"></div>
        <div class="sidebar-context">
            <span class="context-led"></span>
            <div><strong>{{ $actor->role->name }}</strong><small>{{ $actor->branch ? $actor->branch->name . ' · ' . $actor->branch->area : 'Seluruh cabang' }}</small></div>
        </div>
        @include('partials.staff-nav')
        <div class="sidebar-bottom"><span class="sidebar-bottom-line"></span><span>PURO / INTERNAL USE</span>@if(config('puro.demo'))<small>Client presentation · Local demo</small>@endif</div>
    </aside>
    <main class="staff-main" id="main-content">
        <div class="staff-topbar">
            <div class="staff-breadcrumb"><span>PURO</span><span class="breadcrumb-separator">/</span><strong>{{ $staffLabels[$activeTab] ?? 'Dashboard' }}</strong></div>
            <div class="staff-controls">
                <span class="staff-role-chip">{{ $actor->role->name }}</span>
                <span class="staff-account">{{ $actor->username }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-ghost btn-sm" type="submit">Keluar ↗</button>
                </form>
            </div>
        </div>
        <div class="staff-content">{{ $content }}</div>
    </main>
</div>
