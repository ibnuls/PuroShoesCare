<div class="container page-shell">
    <div class="page-intro">
        <div><span class="eyebrow">BOOK YOUR TREATMENT</span>
            <h1>Ready for <em class="accent-word">a refresh?</em></h1>
        </div>
        <p>Tanpa akun. Simpan kode pesanan setelah booking untuk mengikuti semua proses.</p>
    </div>@include('partials.feedback')
    <form wire:submit="submit" class="booking-layout">
        <div class="panel panel-pad">
            <h2>01 / Detail pemesanan</h2>
            <div class="form-grid"><label>Nama penerima<input class="field" wire:model="d.name" autocomplete="name"
                        required maxlength="100"></label><label>Nomor WhatsApp<input class="field" wire:model="d.phone"
                        type="tel" autocomplete="tel" placeholder="08xxxxxxxxxx"
                        required></label><label>Cabang<select class="field" wire:model.live="d.branch">
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }} · {{ $b->area }}</option>
                        @endforeach
                    </select>
                </label><label>Treatment<select class="field" wire:model.live="d.service">
                        @foreach ($services as $s)
                            <option value="{{ $s->id }}">{{ $s->latestTariff?->display_name }} ·
                                {{ $s->category->name }}</option>
                        @endforeach
                    </select>
                </label></div>
            @if ($service?->latestTariff?->express_available)
                <label class="check-label"><input type="checkbox" wire:model.live="d.express"> Minta One Day / 24 Hours
                    (+Rp25.000 per barang). Ketersediaan dikonfirmasi staf.</label>
            @endif
            <h2 class="form-subhead">02 / Barang yang dirawat</h2>
            <p class="muted small">Satu layanan per pesanan, maksimal 20 barang. Foto JPG/PNG/WebP, maksimal 5 MB per
                foto.</p>
            @foreach ($d['items'] as $i => $item)
                <fieldset class="item-editor" wire:key="item-{{ $item['_key'] ?? $i }}">
                    <legend>Barang {{ $i + 1 }}</legend><label>Jenis / bahan / warna<input class="field"
                            wire:model="d.items.{{ $i }}.description"
                            placeholder="Contoh: sneakers kanvas putih" required maxlength="150"></label><label>Kondisi
                        / catatan
                        <textarea class="textarea" wire:model="d.items.{{ $i }}.condition" maxlength="500"
                            placeholder="Bagian kotor atau rusak yang perlu diperhatikan"></textarea>
                    </label><label>Foto kondisi
                        {{ $estimate && $estimate['needsAssessment'] ? '(wajib 1–3 foto)' : '(opsional, maks. 3 foto)' }}<input
                            class="field" type="file" wire:model="photos.{{ $i }}" multiple
                            accept="image/jpeg,image/png,image/webp"></label>
                    <div wire:loading wire:target="photos.{{ $i }}" class="muted small">Mengunggah foto…
                    </div>
                    @if (isset($photos[$i]))
                        <p class="small">{{ count($photos[$i]) }} foto siap dikirim</p>
                        @endif @if (count($d['items']) > 1)
                            <button type="button" class="btn btn-ghost btn-sm"
                                wire:click="removeItem({{ $i }})">Hapus barang</button>
                        @endif
                </fieldset>
            @endforeach
            <button type="button" class="btn btn-ghost" wire:click="addItem" @disabled(count($d['items']) >= 20)>＋ Tambah
                barang</button>
            <h2 class="form-subhead">03 / Penyerahan barang</h2>
            <div class="mode-options">
                @foreach (\App\Support\Puro::MODES as $value => $label)
                    <label class="choice-card"><input type="radio" wire:model.live="d.mode"
                            value="{{ $value }}"> {{ $label }}</label>
                @endforeach
            </div>
            @if ($d['mode'] !== 'DROPOFF')
                <label>Alamat lengkap
                    <textarea class="textarea" wire:model="d.address" autocomplete="street-address" minlength="10" maxlength="500" required></textarea>
                </label>
            @endif
            @if ($d['mode'] === 'BOTH')
                <div class="form-grid"><label>Estimasi jarak dari cabang (km)<input class="field"
                            wire:model.live.debounce.500ms="d.distance" type="number" min="0" max="200"
                            step="0.01" required></label>
                    <div class="gps-control" x-data="{ error: '' }"><button type="button" class="btn btn-ghost"
                            @click="error=''; if(!navigator.geolocation){error='GPS tidak tersedia.';return}; navigator.geolocation.getCurrentPosition(p=>$wire.gps(p.coords.latitude,p.coords.longitude),()=>error='Lokasi tidak tersedia. Isi jarak secara manual.',{timeout:12000})">Gunakan
                            GPS ↗</button>
                        <p x-text="error" class="small red-text"></p>
                    </div>
                </div>
                <p class="small muted">GPS menghitung jarak garis lurus. Tinjau estimasi sebelum booking; untuk jarak
                    operasional khusus, konfirmasi admin terlebih dahulu.</p>
            @endif
            <label>Catatan tambahan
                <textarea class="textarea" wire:model="d.notes" maxlength="1000"></textarea>
            </label>
        </div>
        <aside class="panel panel-pad booking-summary"><span class="eyebrow">YOUR CARE SUMMARY</span>
            <h2>{{ $service?->latestTariff?->display_name ?? 'Pilih treatment' }}</h2>
            <p class="muted">{{ count($d['items']) }} barang · {{ $service?->latestTariff?->duration_text }}</p>
            <label>Kode promo<input class="field" wire:model.live.debounce.500ms="d.promo" maxlength="32"
                    placeholder="Opsional"></label>
            @if ($pricingError)
                <div class="notice warn">{{ $pricingError }}</div>
                @endif @if ($estimate)
                    <div class="summary-row"><span>Estimasi
                            treatment</span><strong>{{ \App\Support\Puro::money($estimate['minimum']) }}@if ($estimate['maximum'] !== $estimate['minimum'])
                                – {{ \App\Support\Puro::money($estimate['maximum']) }}
                            @endif
                        </strong>
                    </div>
                    <div class="summary-row"><span>Jemput satu
                            kali</span><strong>{{ \App\Support\Puro::money($estimate['pickup']) }}</strong></div>
                    <div class="summary-row"><span>Estimasi
                            potongan</span><strong>−{{ \App\Support\Puro::money($estimate['discountMin']) }}</strong>
                    </div>
                    <div class="summary-row summary-total"><span>Estimasi
                            total</span><strong>{{ \App\Support\Puro::money($estimate['totalMin']) }}@if ($estimate['totalMax'] !== $estimate['totalMin'])
                                – {{ \App\Support\Puro::money($estimate['totalMax']) }}
                            @endif
                        </strong>
                    </div>
                    <div class="notice">
                        {{ $estimate['needsAssessment'] ? 'Staf akan menilai kondisi dan mengirim penawaran. Kamu perlu menyetujui harga sebelum membayar.' : 'Tarif tetap. Setelah booking, lanjutkan pembayaran pada halaman status.' }}
                    </div>
                @endif
                <button class="btn btn-lime btn-wide" type="submit" wire:loading.attr="disabled"
                    wire:target="submit,photos">Buat pesanan & simpan kode ↗</button>
                <p class="small muted">Kode pesanan langsung tersedia. Kode bukan tanda pembayaran telah diverifikasi.
                </p>
        </aside>
    </form>
</div>
