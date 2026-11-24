<div class="dashboard-intro"><div>
    <span class="eyebrow">SUPER ADMIN / ACCESS CONTROL</span><h1>Role & Permission.</h1>
    <p>Super Admin bisa menyesuaikan izin operasional per role. Pengaturan cabang, QRIS, dan akun Admin dikelola Super Admin.</p>
</div></div>
@include('partials.feedback')
<div class="panel panel-pad">
    <h2>Hak akses operasional.</h2>
    <form wire:submit="savePermissions">
        <div class="permissions-grid">
            @foreach ($rows as $role)
                <div class="permission-role" wire:key="permission-role-{{ $role->id }}">
                    <h3>{{ $role->name }}</h3>
                    @foreach ($grantGroups[$role->code] as $label => $members)
                        <label class="permission-line">
                            <input type="checkbox" wire:change="setGrantGroup('{{ $role->code }}', {{ $loop->index }}, $event.target.checked)" @checked(array_diff($members, $roleGrants[$role->code] ?? []) === [])>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                    <details class="permission-detail">
                        <summary>Izin per aksi</summary>
                        @foreach (\App\Services\Masters::PERMISSIONS[$role->code] as $code)
                            <label class="permission-line"><input type="checkbox" wire:model.live="roleGrants.{{ $role->code }}" value="{{ $code }}"><span>{{ $permissionLabels[$code] }}</span></label>
                        @endforeach
                    </details>
                </div>
            @endforeach
        </div>
        <button class="btn btn-dark permissions-save" type="submit">Simpan permission ↗</button>
    </form>
    <div class="notice permissions-save">Admin Cabang hanya mengakses data cabangnya. Teknisi dan kurir mengakses tugas yang ditugaskan kepadanya.</div>
</div>
