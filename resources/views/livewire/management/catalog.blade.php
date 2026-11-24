@use('App\Support\Puro')
<div class="dashboard-intro">
    <div><span class="eyebrow">SUPER ADMIN / MEDIA</span><h1>Konten Beranda.</h1><p>Ganti satu gambar lengkap untuk setiap layanan. Atur angka pembayaran saat mengganti harga; foto cabang dan promo global tetap tersedia di tab masing-masing.</p></div>
    @if ($mediaTab === 'services')
        <div class="dashboard-intro-actions media-toolbar">
            <button type="button" class="btn btn-ghost btn-sm" wire:click="openCategory">＋ Tambah kategori</button>
            <button type="button" class="btn btn-dark btn-sm" wire:click="editCatalogue">＋ Tambah layanan</button>
        </div>
    @endif
</div>
@include('partials.feedback')
<div class="filter-bar v3610-media-management-tabs" aria-label="Konten Beranda">
    @foreach (['services' => 'Layanan', 'branches' => 'Cabang', 'promos' => 'Promo Global'] as $key => $label)
        <button type="button" class="filter-chip {{ $mediaTab === $key ? 'active' : '' }}" wire:click="selectMedia('{{ $key }}')" aria-pressed="{{ $mediaTab === $key ? 'true' : 'false' }}">{{ $label }}</button>
    @endforeach
</div>
<section class="panel panel-pad">
    @forelse ($rows as $entry)
        <div class="v3610-photo-card" wire:key="gallery-{{ $mediaTab }}-{{ $entry->id }}">
            @if ($mediaTab === 'services')
                @php
                    $tariff = $entry->latestTariff;
                    $image = $tariff->media_hidden ? null : ($tariff->poster?->url ?? Puro::stockPoster($entry->code, $tariff) ?? route('poster', $tariff));
                @endphp
                <div class="v3610-photo-thumb">
                    @if ($image)<img src="{{ $image }}" alt="Contoh gambar {{ $tariff->display_name }}" loading="lazy">@else<span>PURO</span>@endif
                </div>
                <div>
                    <h3>{{ $tariff->display_name }}{{ in_array($entry->code, ['deep', 'cap-hat', 'helmet-care', 'clutch', 'shoulder', 'backpack'], true) ? ' · Unggulan' : '' }}</h3>
                    <p>Satu gambar lengkap dengan penjelasan tepat di bawah foto. Kartu foto 16:9. Harga pembayaran: {{ Puro::tariffPrice($tariff) }}.</p>
                    <div class="v3610-photo-actions">
                        <button type="button" class="btn btn-dark btn-sm" wire:click="edit({{ $entry->id }})" aria-label="Ganti gambar {{ $tariff->display_name }}">{{ $image ? 'Ganti gambar' : 'Tambah gambar' }}</button>
                        <button type="button" class="btn btn-ghost btn-sm" wire:click="edit({{ $entry->id }})" aria-label="Atur harga pembayaran {{ $tariff->display_name }}">Atur harga pembayaran</button>
                        <button type="button" class="btn btn-danger-ghost btn-sm" wire:click="updateServiceMedia({{ $entry->id }}, false)" @disabled(!$image) wire:confirm="Sembunyikan gambar layanan ini dari katalog?">Hapus gambar</button>
                        <button type="button" class="btn btn-ghost btn-sm" wire:click="updateServiceMedia({{ $entry->id }}, true)">Contoh bawaan</button>
                        @if (Puro::catalogueEntry($entry->code))
                            <a class="btn btn-ghost btn-sm" href="{{ asset('assets/service-posters/' . $entry->code . '.svg') }}" download>Unduh contoh</a>
                        @endif
                        <button type="button" class="btn btn-danger-ghost btn-sm" wire:click="deleteService({{ $entry->id }})" wire:confirm="Hapus layanan dari katalog? Pesanan yang sudah dibuat tetap tersimpan." aria-label="Hapus layanan {{ $tariff->display_name }}">Hapus layanan</button>
                    </div>
                </div>
            @else
                @php
                    $image = $mediaTab === 'branches' ? Puro::branchCover($entry) : Puro::promoCover($entry);
                    $name = $mediaTab === 'branches' ? $entry->name . ' · ' . $entry->area : $entry->code;
                @endphp
                <div class="v3610-photo-thumb">
                    @if ($image)<img src="{{ $image }}" alt="Foto {{ $name }}" loading="lazy">@else<span>PURO</span>@endif
                </div>
                <div><h3>{{ $name }}</h3><p>{{ $mediaTab === 'branches' ? 'Foto tampil pada kartu cabang di Beranda.' : 'Foto tampil di kartu Promo Global pada Beranda.' }}</p>
                    <div class="v3610-photo-actions">
                        <button type="button" class="btn btn-dark btn-sm" wire:click="editCover({{ $entry->id }})" aria-label="Ganti foto {{ $name }}">{{ $image ? 'Ganti foto' : 'Tambah foto' }}</button>
                        <button type="button" class="btn btn-danger-ghost btn-sm" wire:click="updateCover({{ $entry->id }}, false)" @disabled(!$image) wire:confirm="Hapus foto publik ini?">Hapus foto</button>
                        <button type="button" class="btn btn-ghost btn-sm" wire:click="updateCover({{ $entry->id }}, true)">Foto bawaan</button>
                    </div>
                </div>
            @endif
        </div>
    @empty
        <p class="muted">{{ $mediaTab === 'promos' ? 'Buat promo global terlebih dahulu di menu Promo Global.' : 'Belum ada data.' }}</p>
    @endforelse
</section>

@if ($catalogEditing || $categoryEditing || $coverEditing)
    <dialog @class(['modal-box puro-native-modal', 'v3610-service-editor' => $catalogEditing]) wire:ignore.self wire:key="media-editor-{{ $catalogEditing ? 'service-' . ($editing ?: 'new') : ($coverEditing ? 'cover-' . $coverEditing : 'category') }}"
        x-data x-init="$nextTick(() => $el.showModal())" @cancel.prevent="$wire.clear()" @click.self="$wire.clear()" aria-labelledby="media-editor-title">
        <div class="puro-dialog-content">
            <div class="modal-head">
                <div><span class="eyebrow">{{ $editing ? 'SUPER ADMIN / GAMBAR LAYANAN' : 'KONTEN BERANDA' }}</span><h2 id="media-editor-title">{{ $categoryEditing ? 'Tambah kategori' : ($coverEditing ? 'Unggah foto' : ($editing ? 'Gambar & harga.' : 'Buat data baru')) }}</h2></div>
                <button type="button" class="icon-close" wire:click="clear" aria-label="Tutup">×</button>
            </div>
            @include('partials.feedback')
            @if ($categoryEditing)
                <form wire:submit="addCategory">
                    <div class="field-wrap"><label for="category-name">Nama kategori</label><input class="field" id="category-name" wire:model="categoryName" maxlength="80" required></div>
                    <button class="btn btn-dark" type="submit">Tambah kategori</button>
                </form>
            @elseif ($coverEditing)
                <form wire:submit="saveCover">
                    <div class="field-wrap"><label for="new-public-photo">Gambar baru</label><input type="file" id="new-public-photo" class="field" wire:model="cover" accept="image/jpeg,image/png,image/webp" required></div>
                    <button type="submit" class="btn btn-dark" wire:loading.attr="disabled">Simpan foto ↗</button>
                </form>
            @else
                @include('livewire.management.service-editor')
            @endif
        </div>
    </dialog>
@endif
