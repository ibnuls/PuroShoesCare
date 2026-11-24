<div class="payment-review">
    <h3>Periksa bukti pembayaran · kiriman {{ $s->attempt_no }}</h3>
    <p class="help-text">Menolak bukti memberi customer kesempatan mengirim ulang. Pesanan tetap tersimpan.</p>
    <div class="field-wrap"><label for="checked-reference">Ketik ulang nomor referensi</label><input class="field" id="checked-reference" wire:model="typedReference" autocomplete="off" maxlength="80" placeholder="Ketik ulang nomor referensi dari bukti"></div>
    <label class="checkbox-line"><input type="checkbox" wire:model="bankChecked"> Nominal dan referensi sudah cocok dengan transaksi masuk.</label>
    <div class="field-wrap"><label for="payment-reason">Alasan jika bukti ditolak</label><textarea class="textarea" id="payment-reason" wire:model="note" maxlength="1000" placeholder="Contoh: nomor referensi tidak ditemukan pada transaksi masuk"></textarea></div>
    <div class="payment-review-actions">
        <button class="btn btn-dark" type="button" wire:click="review({{ $s->id }}, true)" wire:confirm="Verifikasi pembayaran ini? Pastikan mutasi benar." wire:loading.attr="disabled">Setujui pembayaran</button>
        <button class="btn btn-ghost" type="button" wire:click="review({{ $s->id }}, false)" wire:confirm="Tolak bukti? Customer dapat mengirim ulang.">Tolak bukti pembayaran</button>
    </div>
</div>
