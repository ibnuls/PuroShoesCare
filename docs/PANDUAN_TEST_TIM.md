# Panduan mencoba PURO bersama tim

## 1. Persiapan

Ikuti README sampai aplikasi terbuka dan akun demo tersedia. Semua penguji yang ingin melihat transaksi yang sama harus terhubung ke **server Laravel dan PostgreSQL yang sama**. Menyalin ZIP dan membuat database masing-masing menghasilkan data terpisah.

Untuk satu jaringan Wi-Fi, jalankan `php artisan serve --host=0.0.0.0 --port=8000`, set `APP_URL=http://IP-komputer-server:8000`, lalu `php artisan optimize:clear`. Buka IP server dari HP/laptop teman. Izinkan port 8000 hanya pada jaringan lokal di firewall. Kamera/GPS/clipboard browser bisa memerlukan HTTPS; pengisian manual tetap tersedia.

Untuk uji beberapa peran, gunakan browser berbeda, jendela incognito terpisah, atau profil terpisah. Login pada tab yang berbagi session cookie akan mengganti akun di tab lain.

## 2. Skenario wajib

| Kasus | Langkah | Hasil yang diharapkan |
| --- | --- | --- |
| Katalog | Buka Layanan dan navigasi kategori | Lima kategori, 17 layanan awal; FAQ paling bawah dan shortcut setelah Cap & Hat |
| Harga tetap | Pilih Woman & Kids Shoes, isi form, klik **Lanjut ke pembayaran** | Checkout menampilkan tagihan dan kode pesanan tanpa penawaran staf |
| Penilaian | Pilih Deep Clean, isi 1–3 foto per barang | Booking menunggu penilaian, belum meminta pembayaran |
| Persetujuan | Admin terbitkan harga per barang dalam rentang. Customer buka tracking | Customer bisa menyetujui versi penawaran aktif untuk lanjut ke checkout, atau menolaknya |
| Versi penawaran | Buka penawaran di dua jendela; admin terbitkan versi baru | Tindakan pada penawaran lama ditolak |
| Biaya jemput | Pilih BOTH dan jarak 4, 4.2, 6.2 km | Tarif awal Rp0, Rp5.000, Rp15.000 sekali per pesanan, bukan per barang |
| Bukti pembayaran | Isi referensi unik, nominal tepat dan unggah JPG/PNG/WebP | Menunggu verifikasi admin; belum dianggap lunas |
| Bukti ditolak | Admin isi alasan lalu Tolak bukti | Order tetap aktif, customer bisa kirim ulang dengan referensi sama pada order yang sama |
| Referensi duplikat | Pakai referensi order lain | Ditolak oleh server |
| Verifikasi | Admin ketik ulang referensi dan centang kecocokan mutasi | Satu pembayaran terverifikasi, kode tidak berubah |
| Pembatalan | Admin isi alasan dan Batalkan pesanan sebelum verified | CANCELLED; bukti baru, verifikasi terlambat dan pengerjaan diblokir |
| Penyerahan mandiri | Setelah verified, Admin unggah foto penerimaan | Barang diterima; penugasan teknisi baru tersedia |
| Teknisi | Admin pilih teknisi cabang; teknisi mulai dengan foto BEFORE | CLEANING; teknisi lain/cabang lain tidak dapat mengambil aksi |
| Hasil treatment | Teknisi unggah AFTER dan catatan | Menunggu QC |
| QC rework | QC isi instruksi dan minta rework | Siklus baru dibuat; foto dan keputusan siklus lama dipertahankan |
| Selesai | QC luluskan; DROPOFF diambil atau kurir selesaikan pengantaran dengan bukti | COMPLETE. Bukti serah terima dan catatan lokasi dari pengantaran kurir muncul di Cek status |
| Pemeriksaan | Admin minta inspeksi; customer setuju | Pickup/handover baru dibuka setelah consent |
| Treatment ditolak sesudah pickup | Customer tolak penawaran setelah barang diterima | Hanya tagihan jemput yang disetujui jika ada biaya; tidak membuka pengerjaan teknisi |
| Perangkat lain | Salin kode ke incognito, buka Cek status | Kode membuka transaksi yang sama. Bukti pengantaran kurir baru muncul setelah selesai |
| Akun/cabang | Admin02 membuka order Cabang01; teknisi membuka order tanpa penugasan | 403 / akses ditolak |
| Master versi | Super Admin ubah tarif/poster atau QRIS | Booking baru pakai versi baru; order lama tetap punya snapshot lama |
| Laporan | Buat bukti ditolak dan satu verified | Hanya verified dihitung, satu kali per order; CSV dan PDF sesuai cabang serta periode WIB |
| Chat sapaan | Tulis “alo” setelah menanyakan ongkir | Menjawab sapaan, bukan mengulang informasi ongkir |
| RAG | Sesudah embedding, tanya cara pesan/biaya jemput | Jawaban mencantumkan sumber panduan; pertanyaan di luar dokumen diarahkan ke admin |
| Gemini tidak tersedia | Kosongkan key pada lingkungan uji dan clear config | UI tetap berfungsi, muncul bantuan admin dan draf WhatsApp jika nomor tersedia |

Lihat juga [10 skenario revisi tanggal 18 November](REVISI_2026-11-18.md) untuk uji logout, katalog, QRIS, filter, arsip, dan PDF.

## 3. Pemeriksaan HP

Coba lebar 360, 390, 768 dan desktop. Periksa menu, formulir panjang, foto, ringkasan, input nominal, FAQ, daftar admin, laporan dan chatbot. Tidak boleh ada scroll horizontal seluruh halaman; tabel dan bar kategori memang dapat digeser di dalam bagiannya. Coba tutup/buka chatbot, navigasi antarhalaman, kirim pertanyaan, dan refresh browser untuk mengecek riwayat.

Interaksi form dan navigasi `wire:navigate` memakai Livewire. Status customer dipoll setiap 6 detik; dashboard setiap 12 detik dan detail staf setiap 8 detik saat terlihat. Tab yang tidak aktif bisa ditunda browser, sehingga ini bukan push WebSocket instan. Upload tetap melibatkan request HTTP tersendiri.

## 4. Tes otomatis

PHPUnit memakai PostgreSQL sungguhan, HTTP Gemini palsu, serta Livewire test helper. Siapkan `.env.testing` dengan database berakhiran `_testing`, lalu `php artisan test`. Ubah koneksi pada `.env.testing`; jangan mengarahkannya ke data operasional.

Skrip browser opsional ada di `tests/browser/puro_e2e.py`. Pasang Python Playwright dan Chromium, jalankan server mode demo terlebih dahulu, kemudian jalankan skrip. Skrip membuat data demo baru, menjalankan penolakan/kirim ulang/verifikasi, teknisi, QC, pengantaran, manajemen staf, unduh PDF, dan pemeriksaan tampilan 360/390/768 piksel. Hasil disimpan pada `storage/framework/testing/browser`. Jalankan pada database demo terpisah.

Tidak perlu API key untuk tes otomatis PHP. Untuk tes jawaban Gemini sesungguhnya, isi key milik tim dan jalankan `puro:knowledge-sync --embed`. Jangan mengirim uang sungguhan untuk pengujian.
