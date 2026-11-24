@php
    $groups = [
        'Workspace' => $isSuper ? ['dashboard', 'reports'] : ['dashboard'],
        $isSuper ? 'Pengaturan Pusat' : 'Operasional Cabang' => $isSuper ? ['branches', 'promos', 'accounts', 'permissions'] : ['promos', 'accounts', 'reports'],
        'Konten Publik' => ['catalog', 'knowledge'],
    ];
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'reports' => '<path d="M3 4v16h18M8 15v-4m5 4V8m5 7V5"/>',
        'catalog' => '<path d="M4 5h16v14H4zM8 9h8m-8 4h8"/>',
        'branches' => '<path d="M3 10 5 4h14l2 6v10H3zM3 10h18m-13 10v-6h8v6"/>',
        'qris' => '<path d="M3 3h6v6H3zm12 0h6v6h-6zM3 15h6v6H3zm12 0h2v2h-2zm4 4h2v2h-2z"/>',
        'promos' => '<path d="M3 4h9l9 9-8 8-10-10z"/><circle cx="8" cy="9" r="1"/>',
        'accounts' => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2m2-9a3 3 0 0 0 0-6m1 9a5 5 0 0 1 3 5"/>',
        'permissions' => '<path d="M12 2 20 6v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6zM9 12l2 2 4-4"/>',
        'knowledge' => '<path d="M5 3h10l4 4v14H5zM14 3v5h5M8 12h8m-8 4h8"/>',
    ];
@endphp
<nav id="staff-nav" class="staff-navigation" aria-label="Navigasi staf" @click="staffMenu = false">
    @foreach ($groups as $group => $tabs)
        @php
            $visibleTabs = array_filter($tabs, fn ($tab) => $tab === 'dashboard' || $actor->allows($tab));
        @endphp
        @if ($visibleTabs)
            <div class="sidebar-group">
                <div class="sidebar-caption">{{ $group }}</div>
                @foreach ($visibleTabs as $tab)
                    @php
                        $active = $tab === 'dashboard' ? request()->routeIs('staff.dashboard', 'staff.order') : $activeTab === $tab || ($tab === 'branches' && $activeTab === 'qris');
                    @endphp
                    <a href="{{ $tab === 'dashboard' ? route('staff.dashboard') : route('staff.manage', $tab) }}" wire:navigate @class(['active' => $active]) @if($active) aria-current="page" @endif>
                        <svg class="sidebar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" aria-hidden="true">{!! $icons[$tab] !!}</svg>
                        <span>{{ $staffLabels[$tab] ?? 'Dashboard & Pesanan' }}</span>
                        @if ($active) <b class="nav-indicator"></b> @endif
                    </a>
                @endforeach
            </div>
        @endif
    @endforeach
</nav>
