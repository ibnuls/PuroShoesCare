# Validasi PURO setelah revisi tim

Pengujian 22 November 2026 WIB menggunakan PURO Revisi 10 v4 dan catatan tim 18 November sebagai acuan.

| Pemeriksaan | Hasil |
| --- | --- |
| Runtime | Laravel 12.73.0, Livewire 4.4.8, PHP 8.4.24, PostgreSQL 17.11 |
| Migrasi | Migrasi tambahan diuji pada database demo yang sudah terisi. Suite PHP membuat ulang skema pada database pengujian terpisah. |
| PHPUnit dan Livewire | **41 tes lolos dan 266 assertions.** Termasuk validasi poster dan editor lama, transaksi dengan versi tarif sebelumnya, verifikasi dan kirim ulang bukti, cakupan cabang, grup QRIS, logout lima peran, serta foto pengantaran. |
| Laporan PDF | Teks PDF diperiksa. Cakupan cabang, batas hari WIB, nominal pembayaran terverifikasi, dan akses tanpa izin diuji. |
| Tampilan | 13 halaman dibandingkan dengan demo di Chromium pada lebar 1440 dan 390 piksel. Uji interaksi juga mencakup 360 dan 768 piksel. Kontrol tambahan mengikuti revisi tim dan angka dashboard mengikuti transaksi PostgreSQL. |
| Browser | Katalog dan FAQ, booking dua barang dengan foto serta penilaian harga, pembayaran tarif tetap, pelacakan kembali setelah menutup tab, penolakan dan verifikasi bukti, teknisi, QC, foto pengantaran, manajemen staf, QRIS, perubahan harga layanan, unduh PDF, dan logout. |
| RAG | Parsing PDF teks/Markdown, kecocokan sumber, sapaan, kegagalan layanan, dan pengalihan ke admin diuji. Integrasi Gemini menggunakan HTTP fake. |
| Build dan dependensi | Build Vite berhasil dan disertakan. Composer lock serta kebutuhan runtime telah diperiksa. |

Skenario uji dan cara menjalankannya ada di [Panduan pengujian tim](PANDUAN_TEST_TIM.md). Database pengujian harus berakhiran `_testing`. Paket mencakup kode, konfigurasi contoh, aset publik, dan panduan awal pelanggan.

Pengujian Gemini live memerlukan API key tim dan sinkronisasi embedding. Pengujian WhatsApp mencakup pembuatan tautan/draf. Pengujian dilakukan pada server lokal. Docker Compose dan deployment produksi masih perlu divalidasi pada lingkungan tujuan.

## Paket siap jalan untuk Windows

Setup inti diuji dari folder baru dengan PHP dan PostgreSQL 17 di Linux, tanpa menjalankan instalasi dependensi di dalam peluncur. Tes mencakup pembuatan database dan akun, dua port yang sudah digunakan, alur pesanan sampai selesai, serta menjalankan ulang dengan data dan APP_KEY yang sama. Launcher PowerShell, ekstraksi runtime Windows, dan pemuatan DLL perlu verifikasi pada Windows.
