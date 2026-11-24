@php
    $root = $u->isRole('superadmin');
    $isAdminList = $root && $roles->firstWhere('id', (int) $accountRole)?->code === 'admin';
@endphp
<div class="dashboard-intro">
    <div>
        <span class="eyebrow">{{ $root ? 'SUPER ADMIN / IDENTITY' : 'BRANCH / TEAM' }}</span>
        <h1>{{ $root ? 'Akun Admin Cabang.' : 'Orang di cabang ' . str_pad((string) $u->branch_id, 2, '0', STR_PAD_LEFT) . '.' }}</h1>
        <p>{{ $root ? 'Tambah, nonaktifkan, atau hapus akun staf. Riwayat pesanan tetap tersimpan.' : 'Kelola akun teknisi, QC, dan kurir di cabang sendiri.' }}</p>
    </div>
    @if ($root)<span class="pill green">{{ $rows->total() }} {{ $isAdminList ? 'ADMIN' : 'AKUN' }} TERDAFTAR</span>@endif
</div>
@include('partials.feedback')
@include('livewire.management.account-filters')
<div class="{{ $root ? 'settings-grid' : 'team-grid' }}">
    <section class="panel panel-pad">
        <div class="management-heading"><h2>{{ $root ? ($isAdminList ? 'Admin terdaftar.' : 'Staf terdaftar.') : 'Daftar akses staf.' }}</h2></div>
        @include('livewire.management.account-list')
    </section>
    <section class="panel panel-pad">
        <h2>{{ $editing ? 'Perbarui data' : ($root ? 'Tambah admin.' : 'Tambah akun staf.') }}</h2>
        @if (!$root)<p class="muted">Akun baru akan terkunci ke {{ $u->branch->name }}.</p>@endif
        <form wire:submit="save" class="stacked-admin-form">
            <div class="field-wrap"><label for="account-name">Nama staf</label><input class="field" id="account-name" wire:model="d.full_name" required maxlength="100"></div>
            <div class="field-wrap"><label for="account-user">Username{{ $root ? '' : ' (tanpa email)' }}</label><input class="field" id="account-user" wire:model="d.username" required maxlength="40" autocomplete="off" placeholder="{{ $root ? 'contoh: adminbaru' : 'contoh: teknisiandi' }}"></div>
            @if ($root)
                <div class="field-wrap"><label for="account-branch">Cabang penugasan</label><select class="field" id="account-branch" wire:model="d.branch_id">
                    <option value="">Pusat</option>
                    @foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }} · {{ $branch->area }}</option>@endforeach
                </select></div>
            @endif
            <div class="field-wrap"><label for="account-role">Peran</label><select class="field" id="account-role" wire:model="d.role_id">
                @foreach ($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach
            </select></div>
            <div class="field-wrap"><label for="account-password">{{ $editing ? 'Password baru (opsional)' : 'Password (minimal 10 karakter)' }}</label><input class="field" id="account-password" type="password" wire:model="d.password" autocomplete="new-password" minlength="10" maxlength="100" @required(!$editing)></div>
            <label class="checkbox-line"><input type="checkbox" wire:model="d.active"> Akun aktif</label>
            <div class="admin-inline-actions">
                <button class="btn btn-dark" type="submit" wire:loading.attr="disabled">{{ $editing ? 'Simpan perubahan' : ($root ? 'Tambah akun' : 'Tambah staf') }} ↗</button>
                @if ($editing)<button type="button" class="btn btn-ghost" wire:click="clear">Batal edit</button>@endif
            </div>
        </form>
    </section>
</div>
