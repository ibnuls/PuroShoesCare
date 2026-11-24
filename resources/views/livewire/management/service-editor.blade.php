<form wire:submit="save">
    @if ($editing)
        <p class="muted service-design-name">{{ $d['display_name'] }}</p>
        <div class="field-wrap">
            <label for="service-design-file"><strong>Unggah satu gambar yang sudah berisi foto dan kata-kata</strong></label>
            <input class="field" id="service-design-file" type="file" wire:model="poster" accept="image/png,image/jpeg,image/webp" aria-label="Gambar lengkap layanan">
            <small class="help-text">PNG, JPG, atau WebP. Gambar tampil utuh di pop-up. Pratinjau kartu memakai rasio 16:9. Letakkan penjelasan tepat di bawah foto pada gambar yang diunggah.</small>
        </div>
        <label class="checkbox-line service-design-toggle"><input type="checkbox" wire:model="autoPoster"> Gunakan desain contoh otomatis jika tidak mengunggah gambar baru.</label>
        <div class="form-section-title">HARGA UNTUK PEMBAYARAN</div>
        <p class="help-text">Tagihan dihitung dari angka yang kamu isi di bawah.</p>
        <label class="checkbox-line service-design-toggle"><input type="checkbox" wire:model.live="pricePending"> Harga ditentukan setelah penilaian staf.</label>
        <div class="form-grid">
            <div class="field-wrap"><label for="poster-min-price">Harga minimum (Rp)</label><input class="field" id="poster-min-price" type="number" wire:model="d.min_price" min="1" max="100000000" step="1" @disabled($pricePending) @required(!$pricePending)></div>
            <div class="field-wrap"><label for="poster-max-price">Harga maksimum (Rp)</label><input class="field" id="poster-max-price" type="number" wire:model="d.max_price" min="1" max="100000000" step="1" @disabled($pricePending) @required(!$pricePending)></div>
        </div>
        <p class="help-text">Isi kedua angka sama untuk harga tetap. Rentang harga memerlukan penawaran staf dan persetujuan customer.</p>
        <label class="checkbox-line service-design-toggle"><input type="checkbox" wire:model="confirmPosterPrice"> Jika mengunggah gambar, saya sudah mencocokkan harga pada gambar dengan ketentuan pembayaran di atas.</label>
        <div class="notice">Jika harga berubah, unggah gambar dengan harga baru atau gunakan desain contoh otomatis. Pesanan lama tetap memakai tarif saat booking.</div>
        <details class="service-details-editor">
            <summary>Detail layanan</summary>
    @endif
        <div class="form-grid">
            <label>Kategori
                <select class="field" wire:model="d.category_id">
                    @foreach ($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                </select>
            </label>
            <label>Kode layanan<input class="field" wire:model="d.code" required maxlength="40" placeholder="contoh: deep"></label>
        </div>
        <label>Nama layanan<input class="field" wire:model="d.display_name" required maxlength="80"></label>
        <label>Deskripsi<textarea class="textarea" wire:model="d.description" required maxlength="2000"></textarea></label>
        @if (!$editing)
            <label>Jenis tarif
                <select class="field" wire:model.live="d.pricing_mode">
                    <option value="FIXED">Harga tetap</option>
                    <option value="RANGE">Rentang penilaian</option>
                    <option value="QUOTE">Penawaran admin</option>
                </select>
            </label>
            @if ($d['pricing_mode'] !== 'QUOTE')
                <div class="form-grid">
                    <label>Harga minimum (rupiah)<input type="number" class="field" wire:model="d.min_price" min="1" step="1" required></label>
                    <label>Harga maksimum (sama jika tetap)<input type="number" class="field" wire:model="d.max_price" min="1" step="1" required></label>
                </div>
            @endif
        @endif
        <div class="form-grid">
            <label>Satuan<select class="field" wire:model="d.unit"><option value="pasang">Pasang</option><option value="unit">Unit</option></select></label>
            <label>Estimasi pengerjaan<input class="field" wire:model="d.duration_text" required maxlength="80"></label>
        </div>
        <label class="check-label"><input type="checkbox" wire:model="d.express_available"> Mendukung permintaan One Day (+Rp25.000 per barang)</label>
        <label>Catatan untuk customer<textarea class="textarea" wire:model="d.customer_note" maxlength="1000"></textarea></label>
        <label>Foto cover baru (opsional)<input type="file" class="field" wire:model="cover" accept="image/jpeg,image/png,image/webp"></label>
        @if (!$editing)
            <label>Poster tarif<select class="field" wire:model.live="d.poster_source"><option value="AUTO">Otomatis sesuai versi harga</option><option value="CUSTOM">Unggah poster baru</option></select></label>
            @if ($d['poster_source'] === 'CUSTOM')
                <label>Poster baru<input type="file" class="field" wire:model="poster" accept="image/jpeg,image/png,image/webp" required></label>
            @endif
        @endif
        <label class="checkbox-line"><input type="checkbox" wire:model="d.active"> Aktif dan tampil di daftar layanan</label>
    @if ($editing)
        </details>
        <button class="btn btn-dark btn-wide" type="submit" wire:loading.attr="disabled">Simpan gambar & harga ↗</button>
    @else
        <button class="btn btn-dark" type="submit" wire:loading.attr="disabled">Buat data ↗</button>
    @endif
</form>
