@use('App\Support\Puro')
<div class="container page-shell">
    @php
        $tariff = $service?->latestTariff;
        $assessment = $tariff?->needsAssessment((bool) $d['express']);
    @endphp
    <div class="page-intro">
        <div><span class="eyebrow">BOOK A CLEAN / NO ACCOUNT REQUIRED</span><h1>Let's bring your<br><span class="accent-word">pair back.</span></h1></div>
        <p>{{ $assessment ? 'Isi form dan upload kondisi barang. Pantau penawaran melalui Pesanan terakhir, setujui harga, lalu bayar.' : 'Isi form, lanjutkan pembayaran, dan unggah bukti. Kode pesanan tersedia sejak booking dibuat.' }}</p>
    </div>
    @include('partials.feedback')
    <div class="order-grid">
        <div class="panel panel-pad">
            <div class="form-intro">
                <span class="pill green">STEP 01 / 03</span>
                <h2>Form pesanan.</h2>
                <p>Nggak perlu email atau registrasi akun customer.</p>
            </div>
            <form id="order-form" wire:submit="submit">
                <div class="form-section-title">01 — INFORMASI CUSTOMER & BARANG</div>
                <div class="form-grid">
                    <div class="field-wrap"><label for="f-name">Nama lengkap *</label><input class="field" id="f-name" type="text" wire:model="d.name" autocomplete="name" placeholder="Nama penerima" maxlength="100" required></div>
                    <div class="field-wrap"><label for="f-phone">WhatsApp / telepon *</label><input class="field" id="f-phone" type="tel" wire:model="d.phone" autocomplete="tel" placeholder="08xxxxxxxxxx" maxlength="22" required></div>
                    <div class="field-wrap"><label for="f-shoe">Jenis barang *</label>
                        <input class="field" id="f-shoe" type="text" wire:model="d.items.0.description" maxlength="150" required placeholder="{{ match ($service?->category->code) {
                            'bag' => 'Contoh: backpack hitam',
                            'helmet' => 'Contoh: helm full face putih',
                            'headwear' => 'Contoh: topi baseball hitam',
                            default => 'Contoh: sepatu Adidas Samba putih',
                        } }}">
                    </div>
                    <div class="field-wrap"><label for="f-branch">Cabang tujuan *</label><select class="field" id="f-branch" wire:model.live="d.branch" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }} · {{ $branch->area }}</option>
                        @endforeach
                    </select></div>
                    <div class="field-wrap"><label for="f-service">Jenis treatment *</label><select class="field" id="f-service" wire:model.live="d.service" required>
                        @foreach ($services->groupBy('category.name') as $label => $group)
                            <optgroup label="{{ $label }}">
                                @foreach ($group as $item)
                                    <option value="{{ $item->id }}">{{ $item->latestTariff->display_name }} — {{ Puro::tariffPrice($item->latestTariff) }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select></div>
                    <div class="field-wrap"><label for="f-qty">Jumlah barang (1–20) *</label><input class="field" id="f-qty" wire:model.live.debounce.300ms="quantity" type="number" min="1" max="20" step="1" placeholder="1" required></div>
                </div>
                <div class="form-section-title">02 — METODE LAYANAN</div>
                <div class="choice-row">
                    @foreach ([
                        'BOTH' => ['Jemput + antar', 'Tim kurir mengambil dan mengembalikan barang'],
                        'DELIVERY' => ['Antar saja', 'Antar barang ke cabang, kurir mengembalikan'],
                        'DROPOFF' => ['Antar & ambil sendiri', 'Tanpa kurir dan biaya pengantaran'],
                    ] as $value => [$label, $detail])
                        <label class="choice-card"><input type="radio" name="mode" value="{{ $value }}" wire:model.live="d.mode"><strong>{{ $label }}</strong><span>{{ $detail }}</span></label>
                    @endforeach
                </div>
                <div class="form-section-title">03 — ALAMAT & JARAK</div>
                <div class="form-grid">
                    <div class="field-wrap full"><label for="f-address">{{ $d['mode'] === 'DROPOFF' ? 'Alamat (opsional)' : 'Alamat antar / jemput *' }}</label>
                        <input class="field" id="f-address" wire:model="d.address" maxlength="500" @required($d['mode'] !== 'DROPOFF') placeholder="Alamat lengkap, patokan / detail gedung" autocomplete="street-address">
                    </div>
                    <div class="field-wrap"><label for="f-distance">Jarak estimasi dari cabang (km){{ $d['mode'] === 'BOTH' ? ' *' : '' }}</label>
                        <input class="field" id="f-distance" wire:model.live.debounce.300ms="d.distance" type="number" min="0" max="200" step="any" placeholder="Contoh: 3.5" @required($d['mode'] === 'BOTH')>
                    </div>
                    <div class="field-wrap"><span class="field-caption">Keterangan jarak</span><div class="notice booking-distance-note">Masukkan estimasi jarak atau pakai GPS. Admin akan cek ulang tarif operasional sebelum layanan.</div></div>
                    <div class="field-wrap full" x-data="{ error: '' }">
                        <span class="field-caption">Titik pengantaran (opsional)</span>
                        <div class="field-row">
                            <button class="btn btn-ghost" type="button" @click="error = ''; if (!navigator.geolocation) { error = 'GPS tidak tersedia.'; return; } navigator.geolocation.getCurrentPosition(p => $wire.gps(p.coords.latitude, p.coords.longitude), () => error = 'Isi jarak secara manual.', { timeout: 12000 });">◎ Pakai lokasi GPS</button>
                            <span class="help-text">Perkiraan radius dari cabang. Biaya penjemputan hanya sekali di atas {{ $policy->free_km }} km.</span>
                        </div>
                        <p class="help-text red-text" role="status" x-show="error" x-text="error"></p>
                    </div>
                    <div class="field-wrap full"><label for="f-notes">Catatan khusus (opsional)</label><textarea class="textarea" id="f-notes" wire:model="d.notes" maxlength="1000" placeholder="Contoh: noda kopi, sol menguning..."></textarea></div>
                </div>
                @if (config('puro.demo'))
                    <div class="notice warn booking-demo-note">Ini simulasi website. Harga dan biaya kirim adalah ilustrasi. Pembayaran sesungguhnya memerlukan QRIS aktif dan konfirmasi admin.</div>
                @endif
                @if ($tariff?->express_available)
                    <label class="v3610-express"><input type="checkbox" wire:model.live="d.express"><span>
                        <strong>One Day / 24 Hours · +{{ Puro::money($tariff->express_price) }} per {{ $tariff->unit }}</strong>
                        <small>Permintaan pengerjaan cepat perlu dikonfirmasi staf. Penawaran diberikan sebelum pembayaran.</small>
                    </span></label>
                @endif
                @if ($assessment)
                    @include('livewire.booking.conditions')
                @endif
                <button class="btn btn-dark btn-wide" type="submit" @disabled(!$service || $branches->isEmpty()) wire:loading.attr="disabled" wire:target="submit,uploads">{{ $assessment ? 'Kirim untuk penilaian' : 'Lanjut ke pembayaran' }} ↗</button>
            </form>
        </div>
        @include('livewire.booking.summary')
    </div>
</div>
