<p class="muted">{{ $rows->total() }} akun ditemukan</p>
@forelse ($rows as $account)
    <div class="team-person account-list-row {{ $editing === $account->id ? 'is-selected' : '' }}" wire:key="account-{{ $account->id }}">
        <div>
            <strong>{{ $account->full_name }}</strong>
            <small>{{ '@' . $account->username }} · {{ $account->role->name }}{{ $root ? ' · ' . ($account->branch?->name ?? 'Pusat') : '' }}</small>
        </div>
        <div class="admin-inline-actions">
            <span class="pill {{ $account->active ? 'green' : 'neutral' }}">{{ $account->active ? 'Aktif' : 'Nonaktif' }}</span>
            @if ($root || $u->id !== $account->id)
                <button class="btn btn-ghost btn-sm" type="button" wire:click="edit({{ $account->id }})" aria-label="Edit {{ $account->username }}">Edit</button>
            @endif
            @if ($u->id !== $account->id)
                <button class="btn btn-ghost btn-sm" type="button" wire:click="updateAccountAccess({{ $account->id }}, false)">{{ $account->active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                <button class="btn btn-danger-ghost btn-sm" type="button" wire:click="updateAccountAccess({{ $account->id }}, true)" wire:confirm="Hapus akses akun ini? Riwayat pekerjaan tetap tersimpan." aria-label="Hapus akun {{ $account->username }}">Hapus</button>
            @endif
        </div>
    </div>
@empty
    <p class="notice">Tidak ada akun yang cocok dengan filter.</p>
@endforelse
{{ $rows->links('partials.pagination') }}
