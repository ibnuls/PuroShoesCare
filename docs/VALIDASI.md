# Catatan validasi migrasi PURO

Tanggal: 17 November 2026. Sumber: PURO Revisi 10 versi 4.

## Yang sudah dijalankan

| Pemeriksaan | Hasil |
| --- | --- |
| Laravel | v12.73.0 pada PHP 8.4.24 |
| Livewire | v4.4.8 |
| PostgreSQL | 17.11 sungguhan; database aplikasi dan database pengujian terpisah |
| PHPUnit / Livewire helper | **33 tes lolos, 145 assertions** |
| Migrasi | Pasang schema dari nol, rollback dua migrasi terakhir, lalu migrasi ulang berhasil |
| Harga/pembayaran | Snapshot tarif, promo, ongkir satu kali, penawaran lama, nominal, referensi unik, retry, verifikasi dan pembatalan diuji |
| Operasional | Consent pemeriksaan, pickup, penerimaan, teknisi, foto, QC rework, pengembalian dan collection diuji |
| Akses | Peran, cabang, private payment media, akun nonaktif dan halaman manajemen diuji |
| Seeder | Pengulangan seed mempertahankan edit nama cabang, kode layanan, username dan izin yang dicabut |
| RAG | Parsing PDF teks dan Markdown, invalidasi embedding, similarity, source ID, fakta tarif terkini, greeting, outage dan fallback diuji |
| Privasi tracking | Nama, nomor HP dan alamat tidak dirender pada tracking publik |
| Sintaks PHP | Lolos |
| Cache Laravel | route:cache, config:cache, view:cache berhasil; cache lokal dibersihkan sesudah pemeriksaan |
| Composer | Metadata/lock valid; kebutuhan ekstensi sesuai runtime pengujian |
| Frontend | npm run build berhasil; hasil build disertakan |

Tes menggunakan PostgreSQL dengan nama berakhiran `_testing`. TestCase menolak database lain sebelum menjalankan migrasi. Kredensial aplikasi lokal, APP_KEY, data PostgreSQL, unggahan pribadi dan log tidak disertakan dalam ZIP.

## Batas verifikasi

**Gemini live belum diuji** karena API key asli belum diberikan. Tes memakai HTTP fake untuk embedding dan jawaban Gemini; ini memverifikasi integrasi aplikasi, bukan akses model atau kuota akun milik pengguna.

**Tes browser dan tampilan mobile belum terverifikasi.** Chromium dalam runner dibatasi oleh lingkungan, dan pratinjau lokal browser juga tidak dapat dibuka. Skrip Playwright disertakan untuk dijalankan pada localhost tim, beserta checklist manual lebar 360/390/768px. Pemeriksaan Blade dan Livewire PHP tidak menggantikan pengujian JavaScript, upload dan layout pada browser nyata.

**Docker/Compose belum dijalankan** pada lingkungan pembuatan. Berkas tersedia untuk setup lokal; aplikasi PHP dan PostgreSQL diuji melalui runtime lokal, bukan Docker. Belum ada deployment produksi.

Pembayaran tetap diverifikasi manual. WhatsApp hanya membuka draf yang perlu dikirim customer. Tidak ada payment gateway, WhatsApp Business API, inbox admin internal atau import otomatis pesanan localStorage prototype lama.
