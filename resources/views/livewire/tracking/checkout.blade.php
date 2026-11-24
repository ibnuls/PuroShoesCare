@use('App\Support\Puro')
@php
    $quote = $order->currentQuote;
    $qr = $order->qrisVersion ?? $order->branch->qrisAccount?->latestVersion;
    $group = $qr?->account?->group_name ?? $order->branch->qrisAccount?->group_name;
    $retry = $order->status === 'PAYMENT_REJECTED';
@endphp
<div class="container page-shell" wire:poll.6s.visible>
    <div class="page-intro">
        <div><span class="eyebrow">{{ $quote->kind === 'FIXED' ? 'CHECKOUT / PAYMENT' : 'APPROVED QUOTE / PAYMENT' }}</span><h1>Almost <span class="accent-word">clean.</span></h1></div>
        <p>Pembayaran QRIS mengikuti konfigurasi cabang yang diatur Super Admin.</p>
    </div>
    <div class="pay-layout">
        <div class="panel checkout-detail">
            <span class="pill green">{{ $quote->kind === 'FIXED' ? 'STEP 02 / 03' : 'PEMBAYARAN · PENAWARAN V' . $quote->version }}</span><h2>Ringkasan pembayaran</h2>
            @include('partials.feedback')
            @if ($quote->kind === 'FIXED')
                <p class="muted">{{ $order->tariff->display_name }} · {{ $order->branch->name }}</p>
                <div class="checkout-total">{{ Puro::money($order->total()) }}</div>
                <div class="rule checkout-rule"></div>
                <div class="summary-row"><span>Treatment</span><strong>{{ $order->tariff->display_name }} × {{ $order->items_count }}</strong></div>
                <div class="summary-row"><span>Pengiriman</span><strong>{{ Puro::MODES[$order->fulfilment_mode] }}</strong></div>
                <div class="summary-row"><span>Grup QRIS</span><strong>{{ $group ?: 'Belum tersedia' }}</strong></div>
            @else
                @include('livewire.tracking.quote')
            @endif
            <div class="summary-row checkout-code" x-data="puroReceipt" x-init="remember(@js($order->access_code), @js($order->tariff->display_name . ' · ' . $order->branch->name))">
                <span>Kode pesanan</span>
                <div class="inline-actions">
                    <strong class="tracking-token">{{ $order->access_code }}</strong>
                    <button type="button" class="btn btn-ghost btn-sm" @click="copy(@js($order->access_code))" x-text="copied ? 'Tersalin ✓' : 'Salin ⧉'">Salin ⧉</button>
                </div>
            </div>
            @if ($retry)
                <div class="notice warn"><strong>Alasan penolakan</strong><p>{{ $quote->latestSubmission?->review_reason }}</p></div>
            @endif
            <div class="notice warn booking-demo-note">{{ config('puro.demo') ? 'MODE DEMO. Gunakan bukti uji saja. Jangan transfer uang ke QRIS demo.' : 'Konfirmasi pembayaran tetap diperiksa manual oleh Admin Cabang.' }}</div>
            @include('livewire.tracking.payment-fields')
            <a class="btn btn-ghost btn-wide checkout-back" href="{{ route('track', $order->access_code) }}" wire:navigate>Kembali ke tracking</a>
        </div>
        <div class="qr-wrap">
            <span class="eyebrow">QRIS / {{ $group ?: $order->branch->name }}</span>
            <div class="qr-paper">
                @if ($qr)
                    <img src="{{ $qr->media->url }}" alt="QRIS cabang pilihan">
                @else
                    <div class="qr-placeholder"><div class="qr-logo">QRIS</div><strong>BELUM TERSEDIA</strong></div>
                @endif
            </div>
            <span class="pill {{ $qr ? 'green' : 'amber' }}">{{ $qr ? 'QRIS GRUP TERPASANG' : 'QRIS BELUM TERSEDIA' }}</span>
            <p class="qr-branch">Pembayaran untuk {{ $order->branch->name }}</p>
        </div>
    </div>
</div>
