@use('App\Support\Puro')
@php
    $quote = $order->currentQuote;
    $qr = $order->qrisVersion ?? $order->branch->qrisAccount?->latestVersion;
    $closed = in_array($order->status, ['PAYMENT_REJECTED', 'CANCELLED'], true);
@endphp
@include('livewire.tracking.receipt')
<div class="track-info order-heading">
    <div>
        <span class="eyebrow">{{ $order->verifiedPayment ? 'LIVE STATUS / ' . $order->access_code : 'BOOKING PURO' }}</span>
        <h3>{{ $order->tariff->display_name }} · {{ $order->branch->name }}</h3>
        <p class="muted track-updated">Diperbarui: {{ Puro::date($order->updated_at) }}</p>
    </div>
    <div><span class="pill {{ Puro::color($order->status) }}">{{ Puro::STATES[$order->status] }}</span></div>
</div>
@if (!$closed)
    @if (!$order->tariff->needsAssessment($order->express_requested) || $order->verifiedPayment || $order->status === 'PENDING_PAYMENT')
        @include('livewire.tracking.steps')
    @endif
    @if ($order->tariff->needsAssessment($order->express_requested))
        <div class="timeline v3610-assessment-timeline">
            <div class="timeline-step {{ $quote ? 'done' : 'current' }}"><div class="timeline-dot">{{ $quote ? '✓' : '1' }}</div><div><h4>Penilaian kondisi</h4><p>{{ $quote ? 'Staf telah mengajukan harga.' : 'Menunggu pemeriksaan staf.' }}</p></div></div>
            <div class="timeline-step {{ $quote?->decision === 'ACCEPTED' ? 'done' : ($quote ? 'current' : '') }}"><div class="timeline-dot">{{ $quote?->decision === 'ACCEPTED' ? '✓' : '2' }}</div><div><h4>Persetujuan harga</h4><p>{{ $quote?->decision === 'ACCEPTED' ? 'Penawaran v' . $quote->version . ' disetujui customer.' : 'Pembayaran dibuka setelah harga disetujui.' }}</p></div></div>
        </div>
    @endif
    @include('livewire.tracking.inspection')
    @if ($quote && ($quote->kind !== 'FIXED' || $order->status === 'WAITING_PAYMENT'))
        @include('livewire.tracking.quote')
    @endif
    @if ($order->status === 'WAITING_PAYMENT' && $quote?->decision === 'ACCEPTED')
        <div class="v3610-quote-actions"><a class="btn btn-dark" href="{{ route('book.checkout', $order->access_code) }}" wire:navigate>Lanjut ke pembayaran ↗</a></div>
    @endif
@elseif ($order->status === 'PAYMENT_REJECTED' && $quote?->decision === 'ACCEPTED')
    <section class="payment-retry">
        <h3>Kirim ulang bukti pembayaran</h3>
        <p class="help-text">Pakai referensi yang sama jika kamu memperbaiki gambar dari transaksi sebelumnya.</p>
        @include('livewire.tracking.payment-fields', ['retry' => true])
    </section>
@endif
@include('livewire.tracking.delivery-photo')
<div class="notice">Gunakan kode pesanan sejak booking dibuat, atau buka Pesanan terakhir tanpa login. Simpan kode dengan aman untuk menjaga privasi status dan bukti pengantaran.</div>
<details class="payment-review-history">
    <summary>Riwayat status</summary>
    @foreach ($order->history as $event)
        <article><strong>{{ Puro::STATES[$event->to_status] }}</strong><p class="help-text">{{ $event->note }} · {{ Puro::date($event->created_at) }}</p></article>
    @endforeach
</details>
