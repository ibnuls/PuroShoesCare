<div class="container page-shell tracking-shell" wire:poll.6s.visible>
    <div class="success-hero" x-data="puroReceipt" x-init="remember(@js($order->access_code), @js($order->tariff->display_name . ' · ' . $order->branch->name))">
        <div class="success-mark" aria-hidden="true">✓</div><span class="pill green">STEP 03 / 03</span>
        <h2>{{ $order->status === 'PENDING_PAYMENT' ? 'Bukti pembayaran terkirim.' : 'Status pembayaran PURO.' }}</h2>
        <p class="muted">Simpan kode ini buat cek setiap tahap pengerjaan.</p>
        <div class="tracking-code"><strong class="tracking-token">{{ $order->access_code }}</strong><button class="btn btn-ghost btn-sm" type="button" @click="copy(@js($order->access_code))" x-text="copied ? 'Tersalin ✓' : 'Salin ⧉'">Salin ⧉</button></div>
        <div class="success-actions">
            <a class="btn btn-dark" href="{{ route('track', $order->access_code) }}" wire:navigate>Lacak pesanan ↗</a>
            <a class="btn btn-light" href="{{ route('book') }}" wire:navigate>Buat pesanan lagi</a>
        </div>
    </div>
</div>
