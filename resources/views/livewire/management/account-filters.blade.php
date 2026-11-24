<section class="panel panel-pad account-filter-panel" aria-label="Filter akun staf">
    <div class="account-filters">
        <label>Cari akun
            <input type="search" class="field" wire:model.live.debounce.350ms="accountSearch" maxlength="100" placeholder="Nama atau username">
        </label>
        @if ($u->isRole('superadmin'))
            <label>Cabang
                <select class="field" wire:model.live="accountBranch">
                    <option value="">Semua cabang</option>
                    <option value="central">Pusat</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        <label>Peran
            <select class="field" wire:model.live="accountRole">
                <option value="">Semua peran</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </label>
        <label>Status akun
            <select class="field" wire:model.live="accountStatus">
                <option value="">Semua status</option>
                <option value="active">Aktif</option>
                <option value="inactive">Nonaktif</option>
            </select>
        </label>
        <label>Urutkan
            <select class="field" wire:model.live="accountSort">
                @if ($u->isRole('superadmin'))
                    <option value="branch">Nama cabang</option>
                @endif
                <option value="name">Nama staf</option>
                <option value="role">Peran</option>
                <option value="username">Username</option>
            </select>
        </label>
    </div>
</section>
