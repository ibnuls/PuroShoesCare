<div class="container page-shell" wire:poll.8s.visible>
    @include('partials.staff-nav')<div class="page-intro">
        <div><span class="eyebrow">STAFF WORKSPACE</span>
            <h1>Let’s get <em class="accent-word">to work.</em></h1>
        </div>
        <p>{{ auth()->user()->branch?->name ?? 'Seluruh cabang' }} · Pembaruan otomatis</p>
    </div>@include('partials.feedback')<div class="metrics-grid">
        <div class="metric"><span>Total pesanan</span><strong>{{ $counts->sum() }}</strong></div>
        <div class="metric"><span>Menunggu pembayaran
                diperiksa</span><strong>{{ $counts['PENDING_PAYMENT'] ?? 0 }}</strong></div>
        <div class="metric"><span>Perlu penilaian</span><strong>{{ $counts['WAITING_ASSESSMENT'] ?? 0 }}</strong></div>
        <div class="metric"><span>Selesai</span><strong>{{ $counts['COMPLETE'] ?? 0 }}</strong></div>
    </div>
    <div class="filter-bar"><label class="grow">Cari kode / nama<input class="field"
                wire:model.live.debounce.400ms="search" maxlength="100"
                placeholder="Nama customer atau kode lengkap"></label><label>Status<select class="field"
                wire:model.live="status">
                <option value="">Semua status</option>
                @foreach (\App\Support\Puro::STATES as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </label></div>
    <div class="order-list">
        @forelse($orders as $o)
            <a href="{{ route('staff.order', $o->id) }}" wire:navigate class="staff-order-row">
                <div><span class="small muted">{{ $o->access_code }} ·
                        {{ \App\Support\Puro::date($o->created_at) }}</span>
                    <h3>{{ $o->tariff->display_name }}</h3>
                    <p>{{ $o->customer_name }} · {{ $o->branch->name }}</p>
                </div><span
                    class="pill {{ \App\Support\Puro::color($o->status) }}">{{ \App\Support\Puro::STATES[$o->status] }}</span><strong>{{ \App\Support\Puro::money($o->total()) }}
                    ↗</strong>
        </a>@empty<div class="panel panel-pad">
                <h3>Belum ada pesanan di daftar ini.</h3>
                <p>Pesanan tampil sesuai cabang dan penugasan peranmu.</p>
            </div>
        @endforelse
    </div>
    <div class="pagination">{{ $orders->links() }}</div>
</div>
