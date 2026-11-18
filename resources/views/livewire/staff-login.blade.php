<div class="container page-shell login-shell">
    <div class="login-intro"><span class="eyebrow">PURO / STAFF PORTAL</span>
        <h1>Your care.<br><em class="accent-word">Our work.</em></h1>
        <p>Masuk untuk mengelola pesanan dan pekerjaan sesuai peran serta cabang.</p>
    </div>
    <form wire:submit="login" class="panel panel-pad">
        <h2>Masuk staf</h2>@include('partials.feedback')<label>Username<input class="field" wire:model="username"
                autocomplete="username" required maxlength="40"></label><label>Password<input class="field"
                type="password" wire:model="password" autocomplete="current-password" required
                maxlength="100"></label><button type="submit" class="btn btn-lime btn-wide"
            wire:loading.attr="disabled">Masuk ↗</button>
        @if (config('puro.demo') && app()->environment(['local', 'testing']))
            <div class="notice small">Demo lokal: <strong>admin01</strong> / <strong>Demo123!</strong><br>Peran lain:
                superadmin, teknisi01, qc01, kurir01. Panduan akun ada di README.</div>
        @endif
    </form>
</div>
