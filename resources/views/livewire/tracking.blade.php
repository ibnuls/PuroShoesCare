<div>
    @if ($stage === 'checkout' && $order?->currentQuote?->decision === 'ACCEPTED' && in_array($order->status, ['WAITING_PAYMENT', 'PAYMENT_REJECTED'], true))
        @include('livewire.tracking.checkout')
    @elseif ($stage === 'success' && $order)
        @include('livewire.tracking.success')
    @else
        <div class="container page-shell tracking-shell" wire:poll.6s.visible>
            <div class="page-intro puro-public-intro">
                <div><span class="eyebrow">PURO / TRACKING</span><h1>Status <span class="accent-word">pesanan.</span></h1></div>
                <p>Pantau verifikasi pembayaran, penawaran harga, dan progres perawatan barang.</p>
            </div>
            <form wire:submit="lookup" class="track-lookup">
                <label class="sr-only" for="tracking-code">Kode pesanan</label>
                <input id="tracking-code" class="field" wire:model="search" placeholder="Masukkan kode pesanan" maxlength="40" required>
                <button class="btn btn-dark" type="submit">Lacak ↗</button>
            </form>
            @include('partials.feedback')
            @if ($order)
                <div class="track-card track-card-result">
                    @include('livewire.tracking.result')
                </div>
            @elseif ($code)
                <div class="notice warn track-card-result">Kode pesanan tidak ditemukan. Periksa kembali kode yang disimpan saat booking.</div>
            @endif
            @include('partials.recent-orders')
        </div>
    @endif
</div>
