<nav class="staff-tabs" aria-label="Menu staf">
    <a href="{{ route('staff.dashboard') }}" wire:navigate>Pesanan</a>
    @foreach (['catalog' => 'Layanan & media', 'branches' => 'Cabang & ongkir', 'qris' => 'QRIS', 'accounts' => 'Akun staf', 'permissions' => 'Izin peran', 'promos' => 'Promo', 'reports' => 'Laporan', 'knowledge' => 'Dokumen AI'] as $tab => $label)
        @if (auth()->user()->fresh()->allows($tab))
            <a href="{{ route('staff.manage', $tab) }}" wire:navigate
                class="{{ request()->is('staff/manage/' . $tab) ? 'selected' : '' }}">{{ $label }}</a>
        @endif
    @endforeach
</nav>
