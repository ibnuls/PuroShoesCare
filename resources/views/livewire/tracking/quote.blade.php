@use('App\Support\Puro')
@php
    $totals = $quote->totals();
@endphp
<section class="v3610-price-offer">
    <span class="v3610-current-quote">Penawaran v{{ $quote->version }}{{ $quote->kind === 'LOGISTICS' ? ' / ONGKIR SAJA' : '' }}</span>
    <h3>Rincian harga{{ $quote->decision === 'ACCEPTED' && $quote->kind !== 'FIXED' ? ' yang disetujui' : '' }}</h3>
    @foreach ($quote->lines as $line)
        <div class="v3610-offer-line"><span>Barang {{ $loop->iteration }} · {{ Puro::money($line->unit_price) }}{{ $line->express_fee ? ' + One Day ' . Puro::money($line->express_fee) : '' }}</span><strong>{{ Puro::money($line->unit_price + $line->express_fee) }}</strong></div>
    @endforeach
    <div class="v3610-offer-line"><span>Subtotal treatment</span><strong>{{ Puro::money($totals['subtotal']) }}</strong></div>
    <div class="v3610-offer-line"><span>Potongan promo</span><strong>−{{ Puro::money($totals['discount']) }}</strong></div>
    <div class="v3610-offer-line"><span>Ongkir</span><strong>{{ Puro::money($totals['pickup_fee']) }}</strong></div>
    <div class="v3610-offer-line total"><span>Total</span><strong>{{ Puro::money($totals['total']) }}</strong></div>
    @if ($quote->assessment_note)
        <p class="v3610-estimate-hint">{{ $quote->assessment_note }}</p>
    @endif
    @if ($order->status === 'WAITING_APPROVAL' && $quote->decision === 'PENDING')
        <div class="v3610-quote-actions">
            <button class="btn btn-dark" type="button" wire:click="decide({{ $quote->id }}, true)" wire:confirm="Setujui rincian harga dan lanjutkan ke pembayaran?" wire:loading.attr="disabled">Setujui harga & lanjut bayar ↗</button>
            <details class="decision-details">
                <summary class="btn btn-ghost">Tolak penawaran</summary>
                <label>Alasan penolakan<textarea class="textarea" wire:model="reason" maxlength="1000"></textarea></label>
                <button type="button" class="btn btn-ghost" wire:click="decide({{ $quote->id }}, false)" wire:confirm="Tolak penawaran ini?">Simpan penolakan</button>
            </details>
        </div>
    @elseif ($order->status === 'QUOTE_REJECTED')
        <button class="btn btn-ghost" type="button" wire:click="reassess">Minta penilaian ulang</button>
    @endif
</section>
