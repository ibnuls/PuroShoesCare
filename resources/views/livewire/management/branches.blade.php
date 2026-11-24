@use('App\Support\Puro')
@php
    $group = $groups->firstWhere('id', (int) $d['qris_account_id']);
    $activeCount = $rows->where('active', true)->count();
@endphp
<div class="dashboard-intro">
    <div><span class="eyebrow">SUPER ADMIN / BRANCH MANAGEMENT</span><h1>Manajemen Cabang</h1><p>Atur outlet, alamat, koordinat lokasi, serta QRIS dalam satu tempat. Perubahan dikelola Super Admin.</p></div>
    <span class="pill green">{{ $rows->count() }} CABANG</span>
</div>
@include('partials.feedback')
<div class="settings-grid branch-editor-grid">
    <section class="panel panel-pad">
        <div class="management-heading"><h2>Daftar cabang</h2><span class="report-tag active-text">{{ $activeCount }} outlet aktif</span></div>
        <p class="muted">Pilih untuk mengedit cabang beserta QRIS, atau tambahkan outlet baru.</p>
        <div class="branch-stack">
            @foreach ($rows as $row)
                <button type="button" @class(['branch-manage-row', 'selected' => $editing === $row->id]) aria-pressed="{{ $editing === $row->id ? 'true' : 'false' }}" wire:click="edit({{ $row->id }})">
                    <span><strong>{{ $row->name }}</strong><small>{{ str_pad((string) $row->id, 2, '0', STR_PAD_LEFT) }} · {{ $row->area }}</small></span>
                    <span class="pill neutral">{{ $row->qrisAccount->group_name }} ↗</span>
                </button>
            @endforeach
        </div>
        <button class="btn btn-lime branch-add" type="button" wire:click="clear">＋ Tambah cabang baru</button>
        <div class="branch-policy"><strong>Aturan pusat</strong><p>Admin hanya boleh mengurus pesanan, staf operasional, dan promo di cabangnya. Lokasi, QRIS, serta akun Admin dikelola Super Admin.</p></div>
    </section>

    <section class="panel panel-pad">
        <div class="management-heading">
            <div><span class="eyebrow">CABANG / DETAIL</span><h2>{{ $editing ? 'Edit cabang' : 'Tambah cabang baru' }}</h2></div><span class="pill neutral">SUPER ADMIN</span>
        </div>
        <form wire:submit="save" class="branch-edit-form">
            <div class="field-wrap"><label for="branch-name">Nama cabang</label><input class="field" id="branch-name" wire:model="d.name" required maxlength="80" placeholder="Contoh: Cabang 05"></div>
            <div class="field-wrap"><label for="branch-area">Kota / area</label><input class="field" id="branch-area" wire:model="d.area" required maxlength="100" placeholder="Contoh: Bekasi Barat"></div>
            <div class="field-wrap"><label for="branch-address">Alamat lengkap</label><textarea class="textarea" id="branch-address" wire:model="d.address" required maxlength="500" placeholder="Alamat cabang untuk ditampilkan ke customer"></textarea></div>
            <div class="form-grid">
                <div class="field-wrap"><label for="branch-lat">Latitude</label><input class="field" type="number" id="branch-lat" wire:model="d.latitude" min="-90" max="90" step="any" required placeholder="-6.2000"></div>
                <div class="field-wrap"><label for="branch-lng">Longitude</label><input class="field" type="number" id="branch-lng" wire:model="d.longitude" min="-180" max="180" step="any" required placeholder="106.8167"></div>
            </div>
            <div class="qris-in-branch">
                <div class="qris-section-heading"><div><span class="eyebrow">PAYMENT SETTINGS</span><h3>QRIS Cabang</h3></div><span class="pill neutral">{{ $groups->count() }} GRUP QRIS</span></div>
                <div class="field-wrap"><label for="branch-qris">QRIS yang digunakan cabang</label><select class="field" id="branch-qris" wire:model.live="d.qris_account_id" required>
                    @foreach ($groups as $entry)<option value="{{ $entry->id }}">{{ $entry->group_name }}</option>@endforeach
                </select></div>
                <div class="qr-editor-row">
                    <div class="qr-current">
                        @if ($group?->latestVersion)
                            <img src="{{ $group->latestVersion->media->url }}" alt="QRIS {{ $group->group_name }}">
                        @else
                            <div class="qr-empty-state"><span>▦</span><strong>{{ $group?->group_name ?? 'QRIS' }}</strong><small>Belum ada gambar</small></div>
                        @endif
                    </div>
                    <div class="qr-editor-control">
                        <div class="field-wrap"><label for="branch-qris-image">Upload / ganti gambar QRIS (opsional)</label><input class="field" id="branch-qris-image" type="file" wire:model="file" accept="image/png,image/jpeg,image/webp"><small>PNG, JPG, atau WebP, maks. 5 MB.</small></div>
                        <div class="qris-share-notice">Gambar baru berlaku untuk pesanan berikutnya dari cabang yang memakai grup ini.</div>
                        <a class="report-link" href="{{ route('staff.manage', 'qris') }}" wire:navigate>Kelola / tambah grup QRIS ↗</a>
                    </div>
                </div>
            </div>
            <label class="checkbox-line"><input type="checkbox" wire:model="d.active"> Cabang aktif</label>
            <button class="btn btn-dark" type="submit" wire:loading.attr="disabled">{{ $editing ? 'Simpan perubahan cabang' : 'Buat cabang & atur QRIS' }} ↗</button>
        </form>
    </section>
</div>
<details class="panel panel-pad history-panel">
    <summary>Tarif jemput untuk booking baru</summary>
    <form wire:submit="saveShipping">
        <p>4 km pertama gratis. Kelebihan jarak dibulatkan ke atas dan pengembalian gratis.</p>
        <div class="field-wrap"><label for="pickup-rate">Tarif per km (rupiah)</label><input class="field" id="pickup-rate" type="number" min="0" max="100000" wire:model="rate" required></div>
        <button class="btn btn-dark btn-sm" type="submit">Terbitkan tarif baru</button>
    </form>
</details>
