@use('App\Support\Puro')
<section class="v3610-condition-section">
    <div class="form-section-title">04 — KONDISI BARANG & PENILAIAN HARGA</div>
    <div class="notice">{{ $tariff->pricing_mode === 'QUOTE' ? 'Harga ditetapkan setelah penilaian.' : 'Harga ' . Puro::tariffPrice($tariff) . ' per ' . $tariff->unit . ' ditetapkan setelah penilaian.' }} Setiap barang mendapat penilaian sendiri. Upload minimal satu, maksimal tiga foto per barang.</div>
    @foreach ($d['items'] as $i => $item)
        <div class="v3610-condition-card" wire:key="item-{{ $item['_key'] ?? $i }}">
            <h3>Barang {{ $i + 1 }} / {{ $tariff->display_name }}</h3>
            <div class="field-wrap">
                <label for="condition-note-{{ $i }}">Kondisi / bagian yang perlu perhatian</label>
                <textarea id="condition-note-{{ $i }}" class="textarea" wire:model="d.items.{{ $i }}.condition" maxlength="500" placeholder="Contoh: midsole menguning di sisi luar"></textarea>
            </div>
            <div class="v3610-condition-photos">
                @foreach ($photos[$i] ?? [] as $index => $photo)
                    <figure wire:key="booking-photo-{{ $i }}-{{ $photo->getFilename() }}">
                        <img src="{{ $photo->temporaryUrl() }}" alt="Foto kondisi barang {{ $i + 1 }}">
                        <button type="button" wire:click="removePhoto({{ $i }}, {{ $index }})" aria-label="Hapus foto {{ $index + 1 }} barang {{ $i + 1 }}">×</button>
                    </figure>
                @endforeach
            </div>
            @if (count($photos[$i] ?? []) < 3)
                <label class="btn btn-ghost btn-sm v3610-upload-button" wire:key="upload-{{ $i }}-{{ count($photos[$i] ?? []) }}">
                    ＋ Tambah foto kondisi<input type="file" wire:model="uploads.{{ $i }}" multiple accept="image/png,image/jpeg,image/webp" aria-label="Foto kondisi barang {{ $i + 1 }}">
                </label>
            @endif
            <span class="help-text" wire:loading wire:target="uploads.{{ $i }}">Mengunggah foto…</span>
        </div>
    @endforeach
</section>
