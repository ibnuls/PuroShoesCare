@php
    $selectedGroup = $rows->firstWhere('id', $qrisId);
@endphp
<div class="management-layout">
    <section class="panel panel-pad">
        <div class="management-heading">
            <h2>QRIS per grup</h2>
            <span class="pill neutral">{{ $rows->count() }} grup</span>
        </div>
        <label>Grup QRIS
            <select class="field" wire:model.live="qrisId">
                @forelse ($rows as $group)
                    <option value="{{ $group->id }}">{{ $group->group_name }}</option>
                @empty
                    <option value="0">Belum ada grup</option>
                @endforelse
            </select>
        </label>
        @if ($selectedGroup)
            <div class="qris-preview" wire:key="qris-preview-{{ $selectedGroup->id }}">
                @if ($version = $selectedGroup->latestVersion)
                    <img src="{{ $version->media->url }}" class="payment-qr" alt="QRIS {{ $selectedGroup->group_name }}">
                    <p class="small muted">Versi {{ $version->version }} · {{ \App\Support\Puro::date($version->created_at) }}</p>
                @else
                    <div class="qr-empty-state"><span aria-hidden="true">▦</span><strong>Belum ada gambar QRIS</strong></div>
                @endif
                <p><strong>Dipakai oleh</strong> {{ $selectedGroup->branches->pluck('name')->join(', ') ?: 'Belum terhubung ke cabang' }}</p>
            </div>
            <form wire:submit="saveQris" class="category-form">
                <h3>Ganti QRIS grup</h3>
                <label>Gambar QRIS
                    <input class="field" type="file" wire:model="file" wire:key="qris-file-{{ $selectedGroup->id }}" accept="image/jpeg,image/png,image/webp" required>
                </label>
                <p class="small muted">Gambar baru berlaku untuk pesanan baru di cabang yang memakai grup ini.</p>
                <button class="btn btn-lime" type="submit" wire:loading.attr="disabled">Terbitkan QRIS baru ↗</button>
            </form>
        @endif
    </section>
    <form wire:submit="addQrisGroup" class="panel panel-pad">
        <div class="management-heading"><h2>Tambah grup QRIS</h2></div>
        <p>Untuk QRIS khusus, buat satu grup dan pilih grup tersebut di pengaturan cabang yang akan memakainya.</p>
        <label>Nama grup QRIS
            <input class="field" wire:model="qrisGroupName" maxlength="100" placeholder="Contoh: QRIS Cabang 05" required>
        </label>
        <label>Gambar QRIS baru (opsional)
            <input class="field" type="file" wire:model="newQrisImage" accept="image/jpeg,image/png,image/webp">
        </label>
        <p class="small muted">JPG, PNG, atau WebP. Maksimal 5 MB.</p>
        <button class="btn btn-dark" type="submit" wire:loading.attr="disabled">Tambah grup QRIS ↗</button>
        <a class="btn btn-ghost" href="{{ route('staff.manage', 'branches') }}" wire:navigate>Atur cabang ↗</a>
    </form>
</div>
