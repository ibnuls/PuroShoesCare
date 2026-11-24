@use('App\Support\Puro')
@php
    $root = $u->isRole('superadmin');
@endphp
<div class="dashboard-intro">
    <div><span class="eyebrow">{{ $root ? 'SUPER ADMIN / ALL BRANCH PROMOTIONS' : 'BRANCH / CAMPAIGNS' }}</span><h1>{{ $root ? 'Promo Global.' : 'Promo cabang.' }}</h1>
        <p>{{ $root ? 'Kelola penawaran pusat yang berlaku di semua cabang.' : 'Kelola promo khusus cabang. Promo aktif langsung tampil di Beranda customer.' }}</p></div>
    @if (!$promoArchive)<span class="pill green">{{ $rows->where('active', true)->count() }} PROMO AKTIF</span>@endif
</div>
@include('partials.feedback')
<div class="filter-bar" aria-label="Daftar dan arsip promo">
    <button type="button" class="filter-chip {{ !$promoArchive ? 'active' : '' }}" aria-pressed="{{ $promoArchive ? 'false' : 'true' }}" wire:click="showPromoArchive(false)">Daftar promo</button>
    <button type="button" class="filter-chip {{ $promoArchive ? 'active' : '' }}" aria-pressed="{{ $promoArchive ? 'true' : 'false' }}" wire:click="showPromoArchive(true)">Arsip promo</button>
</div>
@if ($promoArchive)
    @include('livewire.management.promo-archive')
@else
    <div class="settings-grid">
        <section class="panel panel-pad">
            <h2>{{ $root ? 'Promo pusat.' : 'Promo yang tersedia.' }}</h2>
            @if ($root)
                <p class="muted">Promo global aktif tampil di Beranda dan berlaku di seluruh cabang.</p>
            @elseif ($activeGlobalPromos->isNotEmpty())
                <div class="notice">Promo global berlaku untuk seluruh cabang <strong>{{ $activeGlobalPromos->join(', ') }}</strong>.</div>
            @endif
            @forelse ($rows as $promo)
                <div class="team-person" wire:key="promotion-{{ $promo->id }}">
                    <div><strong>{{ $promo->code }} · {{ $promo->latestVersion?->percent }}%</strong><small>Maks. {{ Puro::money($promo->latestVersion?->cap_amount) }} · <span class="{{ $promo->active ? 'active-text' : '' }}">{{ $promo->active ? 'Aktif' : 'Nonaktif' }}</span>{{ $root ? ' · ' . ($promo->branch?->name ?? 'Semua cabang') : '' }}</small></div>
                    <div class="admin-inline-actions">
                        <button class="btn btn-ghost btn-sm" type="button" wire:click="edit({{ $promo->id }})" aria-label="Edit promo {{ $promo->code }}">Edit</button>
                        <button class="btn btn-ghost btn-sm" type="button" wire:click="switchPromotion({{ $promo->id }})">{{ $promo->active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        <button class="btn btn-danger-ghost btn-sm" type="button" wire:click="archive({{ $promo->id }})" wire:confirm="Arsipkan promo ini? Riwayat pesanan tetap tersimpan.">Arsipkan</button>
                    </div>
                </div>
            @empty
                <p class="muted">Belum ada promo pada daftar ini.</p>
            @endforelse
            <button type="button" class="btn btn-lime promo-add" wire:click="clear">＋ {{ $root ? 'Buat promo global' : 'Buat promo baru' }}</button>
        </section>
        <section class="panel panel-pad">
            <h2>{{ $editing ? 'Edit promo' : ($root ? 'Buat promo global' : 'Buat promo cabang') }}</h2>
            <form wire:submit="save" class="global-promo-form">
                <div class="field-wrap"><label for="promo-code">Kode promo</label><input class="field" id="promo-code" wire:model="d.code" maxlength="32" required placeholder="Contoh: PURO25"></div>
                <div class="form-grid">
                    <div class="field-wrap"><label for="promo-percent">Diskon (%)</label><input class="field" id="promo-percent" type="number" min="1" max="90" wire:model="d.percent" required></div>
                    <div class="field-wrap"><label for="promo-cap">Batas potongan (Rp)</label><input class="field" id="promo-cap" type="number" min="1" wire:model="d.cap_amount" required></div>
                </div>
                <label class="checkbox-line"><input type="checkbox" wire:model="d.active"> {{ $root ? 'Aktif dan tampil di Beranda' : 'Aktif untuk customer cabang ini' }}</label>
                <div class="v3610-photo-form"><div class="field-wrap"><label for="promo-photo"><strong>Foto promo (opsional)</strong></label><input type="file" id="promo-photo" wire:model="cover" accept="image/png,image/jpeg,image/webp"></div><p class="help-text">Tanpa foto, kartu promo tetap menampilkan rincian penawaran.</p></div>
                <details>
                    <summary>Jadwal{{ $root ? ' dan cakupan' : '' }}</summary>
                    @if ($root)<div class="field-wrap"><label for="promo-scope">Cakupan</label><select id="promo-scope" class="field" wire:model="d.branch_id" @disabled($editing)>
                        <option value="">Global / semua cabang</option>
                        @foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                    </select></div>@endif
                    <div class="form-grid">
                        <div class="field-wrap"><label for="promo-start">Mulai (WIB)</label><input class="field" id="promo-start" type="datetime-local" wire:model="d.starts_at"></div>
                        <div class="field-wrap"><label for="promo-end">Berakhir (WIB)</label><input class="field" id="promo-end" type="datetime-local" wire:model="d.ends_at"></div>
                    </div>
                </details>
                <button class="btn btn-dark" type="submit" wire:loading.attr="disabled">{{ $root ? 'Simpan promo global' : 'Simpan promo' }} ↗</button>
            </form>
        </section>
    </div>
@endif
