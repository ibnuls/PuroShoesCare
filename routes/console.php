<?php

use App\Models\Role;
use App\Models\StaffUser;
use App\Services\Knowledge;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('puro:knowledge-sync {--embed : Kirim chunk baru ke Gemini embedding}', function (Knowledge $knowledge) {
    try {
        $result = $knowledge->sync((bool) $this->option('embed'));
        $this->info("Dokumen: {$result['documents']}; chunk baru di-embed: {$result['embedded']}.");
    } catch (Throwable $e) {
        $this->error($e->getMessage());

        return 1;
    }
})->purpose('Impor Markdown, TXT, PDF teks dan perbarui indeks RAG');
Artisan::command('puro:create-admin {username}', function () {
    $username = strtolower($this->argument('username'));
    if (! preg_match('/^[a-z0-9._-]{3,40}$/', $username)) {
        $this->error('Username 3–40 huruf kecil, angka, titik, garis bawah atau minus.');

        return 1;
    }
    if (StaffUser::where('username', $username)->exists()) {
        $this->error('Username sudah digunakan.');

        return 1;
    }
    $name = $this->ask('Nama lengkap');
    $password = $this->secret('Password baru (minimal 10 karakter)');
    $confirmation = $this->secret('Ulangi password');
    if (! $name || strlen($password ?? '') < 10 || $password !== $confirmation) {
        $this->error('Nama/password tidak valid atau konfirmasi berbeda.');

        return 1;
    }
    $role = Role::where('code', 'superadmin')->firstOrFail();
    StaffUser::create(['username' => $username, 'full_name' => $name, 'password_hash' => Hash::make($password), 'role_id' => $role->id, 'active' => true]);
    $this->info('Super Admin dibuat. Masuk melalui /staff/login.');
})->purpose('Buat Super Admin dengan password tersembunyi');
