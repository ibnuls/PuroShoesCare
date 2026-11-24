<div wire:poll.12s.visible>
    <div class="dashboard-intro">
        <div>
            <span class="eyebrow">OPERATIONS OVERVIEW / {{ mb_strtoupper($selectedBranch?->name ?? $actor->branch?->name ?? 'ALL BRANCHES') }}</span>
            <h1>{{ ['superadmin' => 'Dashboard Super Admin', 'admin' => 'Dashboard Admin Cabang', 'tech' => 'Workstation teknisi', 'qc' => 'QC review desk', 'courier' => 'Dispatch board'][$actor->role->code] }}</h1>
            <p>{{ $sales !== null ? 'Pantau pesanan, pembayaran terverifikasi, dan aktivitas operasional PURO.' : 'Pantau tugas dan pembaruan pesanan sesuai peran lu.' }}</p>
        </div>
        <div class="dashboard-intro-actions">
            @if ($branches->isNotEmpty())
                <label class="dashboard-branch-control" for="dashboard-branch"><span>Filter cabang</span>
                    <select id="dashboard-branch" wire:model.live="branch">
                        <option value="">Semua Cabang</option>
                        @foreach ($branches as $item) <option value="{{ $item->id }}">{{ $item->name }} · {{ $item->area }}</option> @endforeach
                    </select>
                </label>
            @endif
            <span class="report-tag">{{ \App\Support\Puro::date(now()) }}</span>
        </div>
    </div>
    @include('partials.feedback')
    @php
        $isManagement = $actor->isRole('superadmin') || $actor->isRole('admin');
        $number = fn ($value) => str_pad((string) $value, 2, '0', STR_PAD_LEFT);
        $puroMoney = fn ($value) => \App\Support\Puro::money($value);
        $metrics = [
            'orders' => ['Pesanan masuk', $number($counts->sum()), 'Termasuk pesanan yang belum dibayar', '▦'],
            'pending' => ['Menunggu verifikasi', $number($counts['PENDING_PAYMENT'] ?? 0), 'Perlu pemeriksaan bukti QRIS', '◷'],
            'done' => ['Pesanan selesai', $number($counts['COMPLETE'] ?? 0), 'Pengerjaan dan serah terima tuntas', '✓'],
        ];
        if ($sales !== null) {
            $metrics = ['sales' => ['Penjualan terverifikasi', $puroMoney($sales), 'Nilai pembayaran yang disetujui admin', '↗']] + $metrics;
        } else {
            $metrics['work'] = ['Perlu penilaian', $number($counts['WAITING_ASSESSMENT'] ?? 0), 'Menunggu pemeriksaan kondisi', '◎'];
        }
    @endphp
    @if ($isManagement)
    <div class="insights-grid">
        @foreach ($metrics as $key => [$label, $value, $hint, $icon])
            <article class="insight-card insight-{{ $key }}">
                <div class="insight-card-head"><span>{{ $label }}</span><span class="insight-card-icon" aria-hidden="true">{{ $icon }}</span></div>
                <strong>{{ $value }}</strong><small>{{ $hint }}</small>
            </article>
        @endforeach
    </div>
    @else
        <div class="metric-grid">
            @foreach ([
                'Total pesanan cabang' => $counts->sum(),
                'Butuh verifikasi' => $counts['PENDING_PAYMENT'] ?? 0,
                'Dalam proses' => $counts->except(['PENDING_PAYMENT', 'CANCELLED', 'COMPLETE'])->sum(),
                'Selesai' => $counts['COMPLETE'] ?? 0,
            ] as $label => $amount)
                <div class="metric"><span>{{ $label }}</span><strong>{{ $number($amount) }}</strong></div>
            @endforeach
        </div>
    @endif
    @if ($weekly)
        <div class="overview-row {{ $branchSales->isEmpty() ? 'overview-single' : '' }}">
            <section class="report-card">
                <div class="report-card-head"><div><span class="eyebrow">FINANCIAL SNAPSHOT</span><h2>Penjualan 7 hari terakhir</h2></div><span class="report-tag">Pembayaran terverifikasi</span></div>
                @include('partials.staff.weekly-chart')
            </section>
            @if ($branchSales->isNotEmpty())
                <section class="report-card">
                    <div class="report-card-head"><div><span class="eyebrow">PER BRANCH</span><h2>Penjualan per cabang</h2></div></div>
                    @php
                        $topBranch = max(1, $branchSales->max('total'));
                    @endphp
                    <div class="branch-bars">
                        @foreach ($branchSales as $item)
                            <button type="button" @class(['branch-bar-row', 'branch-bar-button', 'is-selected' => $selectedBranch?->id === $item['id']])
                                aria-pressed="{{ $selectedBranch?->id === $item['id'] ? 'true' : 'false' }}" wire:click="$set('branch', '{{ $item['id'] }}')">
                                <div class="branch-bar-meta"><strong>{{ $item['name'] }}</strong><span>{{ $puroMoney($item['total']) }}</span></div>
                                <div class="branch-bar-track"><div class="branch-bar-fill" style="width: {{ ($item['total'] / $topBranch) * 100 }}%"></div></div>
                                <small>{{ $item['count'] }} pembayaran terverifikasi</small>
                            </button>
                        @endforeach
                    </div>
                    <a class="report-link" href="{{ route('staff.manage', 'promos') }}" wire:navigate>Kelola promo global ↗</a>
                </section>
            @endif
        </div>
    @endif
    <section>
        <div class="section-heading order-list-header"><div>
            <h2>{{ $isManagement ? 'Pesanan ' . ($selectedBranch?->name ?? $actor->branch?->name ?? 'semua cabang') : 'Tugas aktif' }} <span class="muted">({{ $orders->total() }})</span></h2>
            <p class="muted">Data pesanan dan status terakhir sesuai akses lu.</p>
        </div></div>
        @if ($isManagement)
            <div class="filter-bar order-filter-toolbar" aria-label="Filter pesanan">
                @if ($branches->isNotEmpty())
                    <label class="order-branch-picker"><span class="sr-only">Pilih cabang untuk daftar pesanan</span>
                        <select class="filter-chip order-branch-dropdown active" wire:model.live="branch">
                            <option value="">Semua Cabang</option>
                            @foreach ($branches as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach
                        </select>
                    </label>
                @endif
                @foreach (['' => 'Semua status', 'PENDING_PAYMENT' => 'Belum diverifikasi', 'PAYMENT_REJECTED' => 'Bukti ditolak', 'CANCELLED' => 'Dibatalkan', 'assessment' => 'Penilaian / Harga', 'active' => 'Dalam proses', 'COMPLETE' => 'Selesai'] as $key => $label)
                    <button type="button" class="filter-chip {{ $status === $key ? 'active' : '' }}" aria-pressed="{{ $status === $key ? 'true' : 'false' }}" wire:click="$set('status', '{{ $key }}')">{{ $label }}</button>
                @endforeach
            </div>
        @endif
        <details class="dashboard-search">
            <summary>Cari pesanan</summary>
            <label class="field-wrap">Cari kode / nama<input class="field" wire:model.live.debounce.400ms="search" maxlength="100" placeholder="Nama customer atau kode lengkap"></label>
        </details>
        <div class="table-wrap">
            <table class="data-table staff-orders-table">
                <thead><tr><th>Kode pesanan / tanggal</th><th>Customer</th><th>Treatment</th><th>Status</th><th>Total</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}">
                            <td><strong>{{ $order->access_code }}</strong><small>{{ \App\Support\Puro::date($order->created_at) }}</small></td>
                            <td><strong>{{ $order->customer_name }}</strong><small>{{ $actor->isRole('superadmin') ? $order->branch->name . ' · ' : '' }}{{ $order->items->first()?->item_description }}</small></td>
                            <td>{{ $order->tariff->display_name }}<small>{{ $order->items_count }} barang · {{ \App\Support\Puro::MODES[$order->fulfilment_mode] }}</small></td>
                            <td><span class="pill {{ \App\Support\Puro::color($order->status) }}">{{ \App\Support\Puro::STATES[$order->status] }}</span></td>
                            <td><strong>{{ $puroMoney($order->total()) }}</strong></td>
                            <td><div class="order-row-actions">
                                <a class="btn btn-dark btn-sm" href="{{ route('staff.order', ['id' => $order->id, 'branch' => $branch, 'status' => $status, 'search' => $search]) }}" wire:navigate aria-label="Lihat detail {{ $order->access_code }}">Lihat detail ↗</a>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">Belum ada pesanan sesuai filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $orders->links('partials.pagination') }}
    </section>
    @if ($orderId)
        <livewire:staff-order :id="$orderId" :back-filters="compact('branch', 'status', 'search')" :key="'staff-order-' . $orderId" />
    @endif
</div>
