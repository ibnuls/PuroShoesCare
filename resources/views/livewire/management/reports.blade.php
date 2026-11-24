@php
    $scope = $u->isRole('superadmin') ? ($branches->firstWhere('id', (int) $reportBranch)?->name ?? 'Semua cabang') : $u->branch->name;
    $money = fn (int $value) => \App\Support\Puro::money($value);
@endphp
<div class="dashboard-intro">
    <div>
        <span class="eyebrow">{{ $u->isRole('superadmin') ? 'SUPER ADMIN / PERFORMANCE' : 'ADMIN CABANG / PERFORMANCE' }}</span>
        <h1>Analitik Penjualan</h1>
        <p>{{ $u->isRole('superadmin') ? 'Bandingkan performa cabang dan cek kontribusi transaksi yang pembayarannya telah diverifikasi.' : 'Cek performa dan transaksi terverifikasi di ' . $scope . '.' }}</p>
    </div>
    @if ($sales)
        <div class="dashboard-intro-actions report-downloads">
            <a href="{{ route('staff.export', $reportParams) }}" class="btn btn-ghost btn-sm">Unduh CSV ↓</a>
            <a href="{{ route('staff.export.pdf', $reportParams) }}" class="btn btn-dark btn-sm">Unduh PDF ↓</a>
        </div>
    @endif
</div>
@include('partials.feedback')
<div class="analytics-toolbar">
    <div class="filter-bar" aria-label="Periode laporan">
        @foreach (['7' => '7 hari', '30' => '30 hari', 'all' => 'Semua waktu'] as $period => $label)
            <button class="filter-chip {{ $reportPeriod === (string) $period ? 'active' : '' }}" type="button" aria-pressed="{{ $reportPeriod === (string) $period ? 'true' : 'false' }}" wire:click="useReportPeriod('{{ $period }}')">{{ $label }}</button>
        @endforeach
        <details class="sales-date-picker">
            <summary class="filter-chip {{ $reportPeriod === 'custom' ? 'active' : '' }}">Pilih tanggal</summary>
            <div class="report-filters">
                <label>Dari (WIB)<input class="field" type="date" wire:model.change="from" required></label>
                <label>Sampai (WIB)<input class="field" type="date" wire:model.change="to" required></label>
            </div>
        </details>
    </div>
    @if ($u->isRole('superadmin'))
        <div class="filter-bar sales-branches" aria-label="Cabang laporan">
            @foreach (['' => 'Semua cabang'] + $branches->pluck('name', 'id')->all() as $id => $label)
                <button class="filter-chip {{ $reportBranch === (string) $id ? 'active' : '' }}" type="button" aria-pressed="{{ $reportBranch === (string) $id ? 'true' : 'false' }}" wire:click="$set('reportBranch', '{{ $id }}')">{{ $label }}</button>
            @endforeach
        </div>
    @endif
</div>
@if ($reportError)
    <p class="notice error" role="alert">{{ $reportError }}</p>
@elseif ($sales)
    <div class="insights-grid analytics-kpis">
        @foreach ([
            ['Penjualan terverifikasi', $money($sales['total']), 'Hanya pesanan dengan pembayaran diverifikasi', '↗'],
            ['Transaksi terverifikasi', $sales['count'], 'Jumlah order yang dikonfirmasi admin', '✓'],
            ['Rata-rata transaksi', $money($sales['count'] ? (int) round($sales['total'] / $sales['count']) : 0), 'Nilai terverifikasi dibagi jumlah order', '▤'],
            ['Belum diverifikasi', $sales['pending'], 'Menunggu pemeriksaan bukti pembayaran', '◷'],
        ] as [$label, $value, $hint, $icon])
            <article class="insight-card {{ $loop->first ? 'insight-sales' : '' }}">
                <div class="insight-card-head"><span>{{ $label }}</span><span class="insight-card-icon" aria-hidden="true">{{ $icon }}</span></div>
                <strong>{{ $value }}</strong><small>{{ $hint }}</small>
            </article>
        @endforeach
    </div>
    <div class="overview-row analytic-main">
        <section class="report-card">
            <div class="report-card-head"><div><span class="eyebrow">BRANCH PERFORMANCE</span><h2>Perbandingan penjualan</h2></div><span class="report-tag">{{ $scope }}</span></div>
            @php
                $highest = max(1, $sales['branches']->max('total'));
            @endphp
            <div class="branch-bars">
                @foreach ($sales['branches'] as $branch)
                    <div class="branch-bar-row {{ $reportBranch === (string) $branch['id'] ? 'is-selected' : '' }}">
                        @if ($u->isRole('superadmin'))
                            <button class="branch-bar-select" type="button" wire:click="$set('reportBranch', '{{ $branch['id'] }}')" aria-label="Lihat penjualan {{ $branch['name'] }}" aria-pressed="{{ $reportBranch === (string) $branch['id'] ? 'true' : 'false' }}"></button>
                        @endif
                        <div class="branch-bar-meta"><strong>{{ $branch['name'] }}</strong><span>{{ $money($branch['total']) }}</span></div>
                        <div class="branch-bar-track"><div class="branch-bar-fill" style="width:{{ $branch['total'] ? max(4, $branch['total'] / $highest * 100) : 0 }}%"></div></div>
                        <small>{{ $branch['count'] }} pembayaran terverifikasi{{ $u->isRole('superadmin') ? ' · Lihat detail ↗' : '' }}</small>
                    </div>
                @endforeach
            </div>
            <p class="report-note">{{ $u->isRole('superadmin') ? 'Klik cabang di atas untuk memfilter transaksi dan melihat pencapaiannya. ' : '' }}Nominal berasal dari nilai pesanan yang sudah diverifikasi.</p>
        </section>
        <section class="report-card">
            <div class="report-card-head"><div><span class="eyebrow">DAILY TREND</span><h2>7 hari terakhir</h2></div></div>
            @include('partials.staff.weekly-chart', ['weekly' => $sales['weekly']])
        </section>
    </div>
    <div class="report-card report-ledger">
        <div class="report-card-head"><div><span class="eyebrow">AUDITABLE SUMMARY</span><h2>{{ $u->isRole('superadmin') ? 'Rekap seluruh cabang' : 'Rekap cabang' }}</h2></div></div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Cabang</th><th>Pesanan masuk</th><th>Sudah dibayar</th><th>Belum verifikasi</th><th>Penjualan terverifikasi</th><th>Rata-rata transaksi</th></tr></thead>
                <tbody>
                    @foreach ($sales['branches'] as $branch)
                        <tr><td><strong>{{ $branch['name'] }}</strong><small>{{ $branch['area'] }}</small></td><td>{{ $branch['orders'] }}</td><td>{{ $branch['count'] }}</td><td>{{ $branch['pending'] }}</td><td><strong>{{ $money($branch['total']) }}</strong></td><td>{{ $money($branch['count'] ? (int) round($branch['total'] / $branch['count']) : 0) }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <section class="report-card report-ledger">
        <div class="report-card-head"><div><span class="eyebrow">JURNAL / {{ $scope }}</span><h2>Transaksi terverifikasi</h2></div></div>
        <div class="table-wrap"><table class="data-table">
            <thead><tr><th>Waktu verifikasi WIB</th><th>Cabang / Layanan</th><th>Jenis</th><th>Status</th><th>Total</th></tr></thead>
            <tbody>
                @forelse ($sales['rows'] as $row)
                    <tr wire:key="sale-{{ $row->id }}"><td>{{ \App\Support\Puro::date($row->verified_at) }}</td><td>{{ $row->branch }}<small>{{ $row->display_name }}</small></td><td>{{ $row->kind === 'LOGISTICS' ? 'Biaya jemput' : 'Treatment' }}</td><td><span class="pill {{ \App\Support\Puro::color($row->status) }}">{{ \App\Support\Puro::STATES[$row->status] }}</span></td><td>{{ $money((int) $row->total) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="empty-state">Belum ada pembayaran terverifikasi pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>
@endif
