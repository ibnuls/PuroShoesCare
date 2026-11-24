@use('App\Support\Puro')
@php
    $step = match ($order->status) {
        'WAITING_PICKUP', 'PICKING_UP', 'WAITING_HANDOVER' => 1,
        'WAITING_TECH', 'CLEANING', 'REWORK' => 2,
        'WAITING_QC' => 3,
        'READY_DELIVERY', 'OUT_FOR_DELIVERY', 'READY_COLLECTION' => 4,
        'COMPLETE' => 5,
        default => 0,
    };
    $labels = ['Pembayaran', 'Penerimaan / penjemputan', 'Pengerjaan teknisi', 'Quality checking', 'Pengantaran / pengambilan', 'Pesanan selesai'];
@endphp
<div class="timeline" aria-label="Tahapan pesanan">
    @foreach ($labels as $i => $label)
        @php
            $done = match ($i) {
                0 => (bool) $order->verifiedPayment,
                1 => (bool) $order->receipt,
                5 => $order->status === 'COMPLETE',
                default => $step > $i && $order->currentQuote?->kind !== 'LOGISTICS',
            };
            $current = !$done && $step === $i;
            $message = match (true) {
                $done && $i === 1 => 'Barang diterima · ' . Puro::date($order->receipt->received_at),
                $done => 'Tahap selesai',
                $current && $order->status === 'PENDING_PAYMENT' => 'Verifikasi pembayaran',
                $current => Puro::STATES[$order->status],
                default => 'Belum dimulai',
            };
        @endphp
        <div @class(['timeline-step', 'done' => $done, 'current' => $current]) @if ($current) aria-current="step" @endif>
            <div class="timeline-dot">{{ $done ? '✓' : $i + 1 }}</div><div><h4>{{ $label }}</h4><p>{{ $message }}</p></div>
        </div>
    @endforeach
</div>
