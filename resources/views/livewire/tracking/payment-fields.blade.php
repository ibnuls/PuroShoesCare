@if ($qr)
    <form wire:key="payment-{{ $quote->id }}-{{ $quote->latestSubmission?->id ?? 'new' }}" wire:submit="pay({{ $quote->id }}, {{ $qr->id }})">
        <div class="payment-inputs">
            <div class="field-wrap"><label for="pay-reference">Nomor referensi transaksi</label><input class="field" id="pay-reference" wire:model="reference" required maxlength="80" placeholder="Nomor pada bukti pembayaran"></div>
            <div class="field-wrap"><label for="pay-amount">Nominal pembayaran (rupiah)</label><input class="field" id="pay-amount" type="number" min="1" step="1" wire:model="amount" required></div>
            <div class="field-wrap"><label for="pay-proof">Bukti pembayaran</label><input class="field" id="pay-proof" type="file" wire:model="proof" accept="image/jpeg,image/png,image/webp" required>
                <small class="help-text">JPG, PNG, atau WebP, maks. 5 MB. Nomor referensi harus terbaca.</small>
            </div>
        </div>
        <label class="checkbox-line"><input type="checkbox" wire:model="confirmed" required> Saya sudah membayar sesuai tagihan dan bukti siap diperiksa admin.</label>
        <button class="btn btn-dark btn-wide" type="submit" wire:loading.attr="disabled" wire:target="pay,proof">{{ $retry ? 'Kirim ulang bukti' : 'Konfirmasi sudah bayar' }} ↗</button>
    </form>
@else
    <div class="notice warn">QRIS belum dipasang untuk cabang ini. Hubungi admin sebelum membayar.</div>
@endif
