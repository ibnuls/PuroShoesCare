# PURO Care — Laravel 12, PostgreSQL, Livewire, Gemini RAG

Migrasi dari **PURO Revisi 10, versi 4** dengan transaksi PostgreSQL dan antarmuka Livewire. Beranda, katalog, form, checkout, tracking, dan portal staf mengikuti susunan demo. FAQ ada di bawah Layanan dengan shortcut setelah Cap & Hat Clean. Teks pilihan aktif berwarna hijau. Admin mengelola foto dan poster melalui **Konten Beranda**.

## Jalankan di Windows

1. Ekstrak seluruh ZIP ke folder baru. Masuk ke folder **PURO_Laravel12** yang berisi `artisan` dan **Mulai-PURO.cmd**.
2. Klik dua kali **Mulai-PURO.cmd**. Dari PowerShell di folder proyek, perintahnya `.\Mulai-PURO.cmd`.
3. Tunggu tulisan **PURO siap dibuka**. Buka alamat yang dicetak di terminal, biasanya [http://127.0.0.1:8000](http://127.0.0.1:8000). Login staf ada di `/staff/login` dengan username **superadmin** dan password demo **Demo123!**. Biarkan terminal terbuka saat memakai web. Tekan **Ctrl+C** untuk berhenti.

Paket ini untuk demo lokal di Windows 64-bit. Dependensi Laravel dan hasil build frontend sudah tersedia. Peluncur memakai PHP yang kompatibel dari PATH atau Laragon, membuat konfigurasi PHP sendiri, lalu memakai PostgreSQL 17 lokal bila tersedia. Jika PHP atau PostgreSQL 17 belum tersedia, peluncur mengunduh runtime yang diperlukan dan memeriksa checksum paketnya. Siapkan internet saat menjalankan pertama kali. Lu bisa melewati pemasangan Composer, Node.js, dan pgAdmin serta pengeditan `php.ini`.

Peluncur membuat akun demo, file **`.env.local`**, dan database PostgreSQL khusus untuk folder ini. Data pesanan lokal dipertahankan saat menjalankan ulang dari folder yang sama. Gunakan alamat yang dicetak terminal jika port 8000 sedang dipakai. Folder lain hasil ekstrak punya database demo tersendiri. Untuk membagikan kode tim, gunakan ZIP ini atau commit kode dari folder proyek. Git mengabaikan `vendor`, `.env.local`, dan database di `.puro-local`.

### Aktifkan Gemini pada paket Windows

Setelah menjalankan aplikasi pertama kali, buka **`.env.local`** di folder proyek dan isi **`GEMINI_API_KEY`**. Isi **`PURO_ADMIN_WHATSAPP`** dengan nomor format `628...` kalau ingin tombol WhatsApp admin muncul. Simpan, hentikan terminal PURO dengan **Ctrl+C**, lalu jalankan lagi **Mulai-PURO.cmd**. Peluncur akan mengindeks ulang potongan panduan yang baru atau belum terindeks.

Buat key di [Google AI Studio](https://aistudio.google.com/apikey). Dua model bawaan sudah ditulis di `.env.local`. Tes dengan bertanya **“Kalau jarak jemput 6 km, berapa biayanya?”** dan periksa rujukan pada jawaban. Jika menambah PDF teks, simpan di `storage/app/knowledge` dan jalankan ulang peluncur.

### Kalau jendela setup menampilkan error

- Kalau unduhan gagal, sambungkan internet lalu jalankan lagi **Mulai-PURO.cmd**. Runtime yang sudah terpasang akan dipakai ulang.
- Kalau muncul pesan DLL atau runtime Visual C++, pasang [Microsoft Visual C++ Redistributable x64](https://aka.ms/vc14/vc_redist.x64.exe) lalu jalankan ulang. Lihat [persyaratan PHP untuk Windows](https://www.php.net/downloads.php?os=windows&version=8.4).
- Kalau PostgreSQL gagal start, lihat `.puro-local/postgres.log`. Kalau web gagal start, lihat `.puro-local/laravel-console.log`. Pesan terakhir di terminal membantu menentukan langkah perbaikan.

Unduhan peluncur menggunakan paket dari [PHP untuk Windows](https://www.php.net/downloads.php?os=windows&version=8.4) dan [PostgreSQL untuk Windows](https://www.postgresql.org/download/windows/).

## Instalasi manual untuk pengembangan

Gunakan bagian ini jika lu memilih setup sendiri, mengembangkan kode, atau menjalankan dari Linux/macOS.

Kebutuhan: **PHP 8.2+** (disarankan 8.4), Composer 2, **PostgreSQL 15+** (diuji 17), ekstensi PHP `pdo_pgsql`, `mbstring`, `fileinfo`, `curl`, `dom`, `xml`, `zip`, `openssl`. Node **22.12+** diperlukan jika mengubah/membangun frontend. ZIP sudah berisi hasil build sehingga Node tidak wajib untuk mencoba aplikasi.

1. Ekstrak proyek, buka terminal di folder ini. Jalankan `composer install`.
2. Salin `.env.example` menjadi `.env`. Di PowerShell: `Copy-Item .env.example .env`; di macOS/Linux: `cp .env.example .env`.
3. Buat role dan database PostgreSQL melalui pgAdmin atau `psql -U postgres`:

```sql
CREATE USER puro WITH PASSWORD 'ganti_password_lokal_ini';
CREATE DATABASE purocare OWNER puro;
CREATE DATABASE puro_testing OWNER puro;
```

4. Sesuaikan `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` pada `.env`. Untuk percobaan teman satu tim, set `APP_ENV=local` dan **`PURO_DEMO_MODE=true` sebelum seeding**.
5. Jalankan:

```bash
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan puro:knowledge-sync
php artisan serve
```

Buka **http://127.0.0.1:8000**. Portal staf: **/staff/login**. Untuk instalasi yang sudah berjalan, ikuti [langkah pembaruan paket dan database](docs/REVISI_2026-11-18.md#memperbarui-instalasi-atau-repo-yang-sudah-ada).

**`npm start` menjalankan Vite untuk pengembangan frontend.** Server Laravel tetap dijalankan dengan `php artisan serve` pada terminal lain. Saat mengubah CSS/JS:

```bash
npm ci
npm start
# atau, untuk hasil build tanpa server Vite:
npm run build
```

## Akun demo

Hanya dibuat jika `PURO_DEMO_MODE=true` dan lingkungan `local`/`testing`. Password semuanya **`Demo123!`**.

| Peran | Username |
| --- | --- |
| Super Admin | `superadmin` |
| Admin Cabang 01–04 | `admin01`, `admin02`, `admin03`, `admin04` |
| Teknisi Cabang 01–04 | `teknisi01` sampai `teknisi04` |
| QC Cabang 01–04 | `qc01` sampai `qc04` |
| Kurir Cabang 01–04 | `kurir01` sampai `kurir04` |

Alamat empat cabang dan gambar QRIS pada mode demo adalah contoh. **Jangan melakukan transfer sungguhan**. Uji dengan gambar contoh sebagai bukti dan referensi unik, lalu verifikasi sebagai simulasi admin.

Untuk lingkungan tanpa akun demo, seed katalog lalu buat Super Admin sendiri:

```bash
php artisan puro:create-admin namausername
```

Perintah meminta nama dan password tersembunyi. Super Admin mengatur akun Admin; Admin hanya mengelola teknisi, QC dan kurir cabangnya.

## Aktifkan Gemini RAG pada instalasi manual

Isi `.env` di server:

```dotenv
GEMINI_API_KEY=isi_kunci_milikmu
GEMINI_MODEL=gemini-3.5-flash-lite
GEMINI_EMBEDDING_MODEL=gemini-embedding-001
PURO_ADMIN_WHATSAPP=628xxxxxxxxxx
```

Lalu:

```bash
php artisan optimize:clear
php artisan puro:knowledge-sync --embed
```

Model bisa diganti melalui `.env` sesuai akses akun Gemini. Model embedding untuk dokumen dan pertanyaan harus sama; jalankan sinkronisasi embedding lagi setelah menggantinya. Nama model bukan API key.

Panduan awal ada di **`storage/app/knowledge/panduan-pelanggan.md`**. Letakkan tambahan **PDF teks, Markdown atau TXT** di folder itu, kemudian jalankan perintah sync. File **tidak cukup hanya ditaruh**: teks harus diimpor dan embedding dibuat. PDF hasil scan perlu OCR dahulu. Maksimal 10 MB per dokumen. Super Admin juga dapat mengunggah, mengaktifkan/menonaktifkan dokumen, serta memulai indeks dari **Dokumen Chatbot**. Customer bisa membaca atau mengunduh panduan melalui footer website.

Chatbot mengubah pertanyaan menjadi embedding, mencari chunk di PostgreSQL menggunakan cosine similarity, lalu meminta Gemini menjawab dengan ID sumber. Jawaban tanpa sumber yang cocok, kegagalan API, dan permintaan admin menghasilkan bantuan admin. Kunci API tetap di server. Belum ada API key asli pada paket ini; pengujian integrasi Gemini memakai respons tiruan, bukan panggilan live.

WhatsApp membuka **draf pertanyaan**; customer meninjau dan menekan Kirim sendiri. Tidak ada pesan otomatis, inbox admin internal atau jaminan admin sudah menerima percakapan. Riwayat chat hanya disimpan 24 jam di browser; kode pesanan terakhir disimpan 30 hari. Pesanan dan riwayat staf tersimpan di PostgreSQL.

## Docker untuk uji lokal

Salin `.env.example` ke `.env`, isi password database, set `APP_URL=http://127.0.0.1:8000` dan `PURO_DEMO_MODE=true` untuk demo.

```bash
docker compose build
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show
```

Salin hasil `base64:...` ke `APP_KEY=` pada `.env`, kemudian:

```bash
docker compose up -d
docker compose logs -f app
docker compose exec app php artisan puro:knowledge-sync --embed
```

PostgreSQL dan unggahan disimpan pada volume bernama. APP_KEY disimpan di `.env`, **tidak dibuat ulang setiap restart**. `docker compose down` menghentikan stack; jangan menambahkan `-v` jika data ingin dipertahankan. Dockerfile/Compose disediakan untuk lokal dan belum dijalankan pada lingkungan pembuatan ini.

## Pengujian dan dokumen

- [Revisi 18 November dan cara update instalasi/GitHub](docs/REVISI_2026-11-18.md)
- [Panduan pengujian tim](docs/PANDUAN_TEST_TIM.md)
- [Arsitektur dan pemetaan prototype](docs/ARSITEKTUR.md)
- [Persiapan server](docs/DEPLOYMENT.md)
- [Catatan validasi](docs/VALIDASI.md)

Jalankan tes pada database terpisah `puro_testing`:

```bash
# Salin .env.testing.example menjadi .env.testing, sesuaikan koneksi DB.
php artisan config:clear
php artisan test
```

TestCase menolak koneksi non-PostgreSQL dan nama database yang tidak berakhiran `_testing` sebelum migrasi tes. Tes mereset database pengujian, bukan database aplikasi.

Data pesanan pada localStorage prototype lama **tidak otomatis diimpor**. Gunakan database baru untuk pengujian; import data lama memerlukan proses migrasi tersendiri.
