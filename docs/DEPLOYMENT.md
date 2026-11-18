# Menyiapkan server PHP dan PostgreSQL

Paket belum diterbitkan ke server produksi. Hosting prototype sebelumnya menjalankan static/Worker, sehingga tidak dapat langsung menjalankan proses PHP dan PostgreSQL Laravel ini.

Gunakan server PHP 8.2+ atau platform Laravel dengan PostgreSQL. Disarankan PHP 8.4, PHP-FPM, Nginx/Apache dan HTTPS. Document root harus menunjuk **folder `public`**, bukan root proyek. Siapkan ekstensi PHP pada README dan storage yang persisten.

1. Gunakan database produksi baru; set `APP_ENV=production`, `APP_DEBUG=false`, `PURO_DEMO_MODE=false`, `SESSION_SECURE_COOKIE=true` dan `APP_URL=https://domain-anda`.
2. Isi akses PostgreSQL dan buat APP_KEY **sekali**. Simpan APP_KEY bersama backup; perubahan key dapat membuat kode tracking dan session terenkripsi lama tidak terbaca.
3. `composer install --no-dev --optimize-autoloader`. Build frontend dengan `npm ci && npm run build` atau gunakan hasil build yang disertakan.
4. `php artisan migrate --seed --force`, `php artisan storage:link`, lalu `php artisan puro:create-admin username`.
5. Isi `GEMINI_API_KEY`, model dan `PURO_ADMIN_WHATSAPP`; `php artisan puro:knowledge-sync --embed`.
6. Atur cabang/alamat, QRIS nyata, tarif, promo dan akun dari Super Admin. Jika database pernah dipakai demo, akun `Demo123!` tidak otomatis dihapus dengan mematikan flag. Gunakan database produksi baru atau nonaktifkan/ganti seluruh akun demo sebelum dibuka ke customer.
7. Pastikan PHP-FPM dapat menulis `storage` dan `bootstrap/cache`. Jalankan `php artisan config:cache`, `php artisan route:cache`, `php artisan view:cache`. Restart proses PHP sesudah mengubah env. Saat mengubah `.env` dalam pengembangan, `php artisan optimize:clear` cukup.

Backup PostgreSQL dan `storage/app/private`, `storage/app/public`, dokumen knowledge serta APP_KEY. Jangan menyajikan folder private melalui alias web server. Hanya `public/storage` yang menunjuk ke disk publik. Atur backup dan retensi berdasarkan kebutuhan usaha.

Konfigurasi awal tidak memakai queue atau scheduler untuk progres; polling Livewire membaca perubahan server. PDF/embedding besar sebaiknya diindeks lewat CLI agar tidak terkena batas waktu request web. Dokumentasi yang diunggah perlu memang berisi informasi yang boleh dibaca customer.

Compose yang disertakan memakai server development Laravel, sesuai tujuan uji tim. Pengujian Docker dan deployment produksi belum dilakukan pada lingkungan pembuatan.
