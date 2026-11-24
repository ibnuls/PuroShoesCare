<div class="container page-shell">
    <div class="auth-layout">
        <section class="auth-aside">
            <div>
                <span class="eyebrow auth-kicker">PURO / OPERATIONS</span>
                <h2>Made for<br>the team<br><span class="accent-word">behind PURO.</span></h2>
                <p>Dashboard operasional staf. Customer nggak butuh akun sama sekali.</p>
            </div>
            <span class="eyebrow">SUPER ADMIN · ADMIN CABANG · TEKNISI · QC · KURIR</span>
        </section>
        <section class="auth-panel">
            <span class="pill neutral">STAFF SIGN IN</span>
            <h1>Akses staf.</h1>
            <p class="muted">Masuk dengan username staf. Super Admin mengelola cabang dan QRIS. Admin Cabang mengelola operasional. Teknisi, QC, dan kurir bekerja sesuai tugas.</p>
            @include('partials.feedback')
            <form wire:submit="login">
                <div class="field-wrap">
                    <label for="staff-username">Username</label>
                    <input class="field" id="staff-username" wire:model="username" placeholder="contoh: admin01" autocomplete="username" maxlength="60" required>
                </div>
                <div class="field-wrap">
                    <label for="staff-password">Password</label>
                    <input class="field" id="staff-password" wire:model="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn btn-dark btn-wide" wire:loading.attr="disabled">Masuk ke dashboard ↗</button>
            </form>
            @if (config('puro.demo') && app()->environment(['local', 'testing']))
                <div class="note-logins"><strong>Akun uji coba:</strong> <code>superadmin</code>, <code>admin01</code>, <code>admin02</code>, <code>admin03</code>, <code>admin04</code>. Peran lain: <code>teknisi01</code>, <code>qc01</code>, <code>kurir01</code>, dan seterusnya hingga 04. Password demo semuanya <code>Demo123!</code>.
                    <div class="notice warn demo-login-note">Demo lokal memakai akun uji yang tersimpan di database. Gunakan akun staf sendiri untuk operasional.</div>
                </div>
            @endif
        </section>
    </div>
</div>
