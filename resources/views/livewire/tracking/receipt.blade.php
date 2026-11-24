@php
    [$state, $tag, $heading, $description] = match (true) {
        $order->status === 'PENDING_PAYMENT' => ['pending', 'MENUNGGU VERIFIKASI ADMIN', 'Bukti pembayaran sedang diperiksa', 'Bukti sudah dikirim. Buka halaman ini atau Pesanan terakhir untuk cek pembaruan.'],
        $order->status === 'PAYMENT_REJECTED' => ['rejected', 'BUKTI DITOLAK · KIRIM ULANG', 'Perbaiki bukti pembayaran', 'Lengkapi bukti dan kirim ulang. Konfirmasi transaksi sebelumnya ke admin sebelum transfer lagi.'],
        $order->status === 'CANCELLED' => ['cancelled', 'PESANAN DIBATALKAN', 'Pesanan dihentikan', 'Simpan kode pesanan untuk menghubungi admin. Pembatalan tidak otomatis mengembalikan dana.'],
        $order->verifiedPayment !== null => ['approved', 'PEMBAYARAN DISETUJUI', 'Pembayaran terverifikasi', 'Admin telah menyetujui pembayaran. Gunakan kode pesanan yang sama untuk memantau proses perawatan barang.'],
        default => ['awaiting', 'BOOKING DITERIMA', 'Kode pesanan sudah tersedia', 'Simpan kode pesanan ini sejak awal. Pantau penawaran harga dan lanjutkan pembayaran dari halaman yang sama.'],
    };
@endphp
<section class="payment-receipt {{ $state }}" data-payment-state="{{ $state }}" aria-label="Status pembayaran dan kode pesanan"
    x-data="puroReceipt" x-init="remember(@js($order->access_code), @js($order->tariff->display_name . ' · ' . $order->branch->name))">
    <span class="eyebrow">{{ $tag }}</span><h3>{{ $heading }}</h3><p>{{ $description }}</p>
    @if ($state === 'rejected')
        <p><strong>Alasan penolakan:</strong> {{ $order->currentQuote?->latestSubmission?->review_reason }}</p>
    @elseif ($order->cancellation)
        <p><strong>Alasan pembatalan:</strong> {{ $order->cancellation->reason }}</p>
        @if ($order->cancellation->return_required)
            <p>Barang sudah diterima cabang. Hubungi admin untuk pengembaliannya.</p>
        @endif
    @endif
    <p class="tracking-code-label">Kode pesanan</p>
    <div class="tracking-code">
        <strong class="tracking-token">{{ $order->access_code }}</strong>
        <button type="button" class="btn btn-ghost btn-sm" @click="copy(@js($order->access_code))" x-text="copied ? 'Tersalin ✓' : 'Salin kode ⧉'">Salin kode ⧉</button>
    </div>
</section>
