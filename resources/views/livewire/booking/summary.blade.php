@use('App\Support\Puro')
<aside class="panel quote-panel">
    <div class="quote-cover"><span>PURO / YOUR PAIR, OUR CARE</span><img class="shoe-art" src="{{ asset('assets/quote-shoe.svg') }}" alt=""></div>
    <div class="quote-body" id="quote-content">
        <h3>Order summary<span class="quote-title-dot">.</span></h3>
        @if ($estimate)
            @php
                $range = fn ($min, $max) => $min === null ? '—' : Puro::money($min) . ($min !== $max ? '–' . Puro::money($max) : '');
            @endphp
            <div class="summary-row"><span>{{ $tariff->display_name }} × {{ count($d['items']) }}</span><strong>{{ $range($estimate['minimum'], $estimate['maximum']) }}</strong></div>
            <div class="summary-row"><span>Promo {{ $promo ? '· ' . $d['promo'] : '' }}</span><strong>−{{ Puro::money($estimate['discountMin'] ?? 0) }}</strong></div>
            <div class="summary-row"><span>Biaya jemput (sekali)</span><strong>{{ $estimate['pickup'] === null ? 'Perlu lokasi' : Puro::money($estimate['pickup']) }}</strong></div>
            <div class="rule quote-rule"></div>
            <div class="summary-row total"><span>{{ $assessment ? 'Estimasi total' : 'Total' }}</span><strong>{{ $range($estimate['totalMin'], $estimate['totalMax']) }}</strong></div>
            @if ($assessment)
                <p class="v3610-estimate-hint">Belum menjadi tagihan. Admin Cabang mengajukan harga per barang. Lu setujui dulu di web sebelum bayar.</p>
            @endif
        @elseif ($pricingError)
            <div class="notice warn">{{ $pricingError }}</div>
        @endif
        <div class="notice">Penjemputan gratis sampai {{ $policy->free_km }} km. Lebih dari {{ $policy->free_km }} km, selisih jarak dibulatkan ke atas × {{ Puro::money($policy->rate_per_km) }}/km dan ditagih sekali. Pengembalian gratis. Estimasi GPS memakai jarak garis lurus.</div>
        <div class="promo-line">
            <input class="field" wire:model="promoInput" placeholder="Kode promo" aria-label="Kode promo" maxlength="32"><button type="button" class="btn btn-ghost btn-sm" wire:click="applyPromo">Pakai</button>
        </div>
    </div>
</aside>
