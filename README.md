# PURO Care — Laravel 12, PostgreSQL, Livewire, Gemini RAG

Migrasi aplikasi dari **PURO Revisi 10, versi 4**. Aplikasi PHP sungguhan dengan transaksi PostgreSQL dan antarmuka Livewire. Foto, logo, kategori dan 17 layanan menggunakan sumber prototype terbaru. Desain hitam/lime dipertahankan; FAQ ada di bawah Layanan dan shortcut setelah Cap & Hat.

## Mulai di komputer sendiri

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

Buka **http://127.0.0.1:8000**. Portal staf: **/staff/login**. Gunakan `php artisan migrate --seed` untuk menambah migrasi; jangan gunakan `migrate:fresh` pada database berisi data penting.

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

## Aktifkan Gemini RAG

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

Panduan awal ada di **`storage/app/knowledge/panduan-pelanggan.md`**. Letakkan tambahan **PDF teks, Markdown atau TXT** di folder itu, kemudian jalankan perintah sync. File **tidak cukup hanya ditaruh**: teks harus diimpor dan embedding dibuat. PDF hasil scan perlu OCR dahulu. Maksimal 10 MB per dokumen. Super Admin juga dapat mengunggah, mengaktifkan/menonaktifkan dokumen, serta memulai indeks dari **Dokumen AI**.

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
