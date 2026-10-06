# Panduan Demo & Skrip Pengujian SIMOKAS (5 Menit)

Dokumen ini memuat panduan demonstrasi aplikasi **SIMOKAS (Sistem Monitoring & Inspeksi Kendaraan Bekas)**, daftar akun demo, langkah-langkah reset data, serta skrip demo 5 menit yang terstruktur untuk pengujian dan evaluasi juri.

---

## 1. Daftar Akun Demo

Seluruh akun demo telah diisi dengan data awal yang realistis di Kota Pekanbaru.

| Peran (Role) | Nama Pengguna | Alamat Email | Kata Sandi (Password) | Keterangan |
|---|---|---|---|---|
| **Admin** | Administrator Utama | `admin@demo.id` | `Password123!` | Akses panel moderasi, antrean inspeksi, audit log, moderasi rating |
| **Penjual (User)** | Budi Santoso | `penjual@demo.id` | `Password123!` | Pemilik listing unit aktif, penawaran transaksi, dan penjual terverifikasi |
| **Pembeli (User)** | Siti Rahma | `pembeli@demo.id` | `Password123!` | Pembeli unit, pemohon inspeksi, pembayar escrow, pemberi rating |
| **User Tambahan** | Hendra Wijaya | `user3@demo.id` | `Password123!` | Pengguna alternatif untuk simulasi multi-user |
| **Inspektor 1** | Agus Kurniawan | `inspektor@demo.id` | `Password123!` | Inspektor tersertifikasi (Mobil & Motor), status Approved, rating aktif |
| **Inspektor 2** | Doni Siregar | `inspektor2@demo.id` | `Password123!` | Inspektor mobil tersertifikasi, status Approved |
| **Inspektor Pending** | Eko Prasetyo | `inspektor_pending@demo.id` | `Password123!` | Inspektor baru menunggu verifikasi berkas KTP & Portofolio oleh admin |

> **PERINGATAN KEAMANAN:**
> Nonaktifkan atau ganti password akun demo setelah lomba selesai (`SEED_DEMO=false` pada lingkungan produksi).

---

## 2. Cara Me-Reset Data Demo

Jika data demo telah dimodifikasi selama pengujian atau presentasi, basis data dapat dikembalikan ke kondisi awal kapan saja dengan menjalankan:

```bash
php artisan migrate:fresh --seed
```

Perintah di atas akan:
1. Membangun ulang seluruh tabel dan skema database.
2. Menjalankan `ReferenceSeeder` (template inspeksi mobil/motor, pengaturan default, akun sistem).
3. Menjalankan `DemoSeeder` (7 akun demo, 17 listing kendaraan di Pekanbaru, 5 hasil inspeksi lengkap beserta kalkulasi skor & foto ber-watermark, 3 job inspeksi aktif/berjalan, 3 transaksi, dan rating transaksi selesai).

---

## 3. Skrip Demo 5 Menit (Step-by-Step)

### Menit 0:00 - 0:45: Publik / Pembeli Menjelajahi Katalog & Hasil Inspeksi
1. Buka halaman utama atau katalog kendaraan di [`/listings`](/listings).
2. Tampilkan filter kendaraan (Mobil / Motor, rentang harga, transmisi, lokasi Pekanbaru).
3. Klik salah satu listing yang telah memiliki badge **Terinspeksi**, misalnya **Toyota Avanza 2019** atau **Honda Brio 2021**.
4. Di halaman detail iklan [`/listings/{id}`](/listings), perhatikan:
   - Skor inspeksi kendaraan (misal 100/100, Rekomendasi: **Layak**).
   - Rating dan jumlah ulasan inspektor yang bertugas.
   - Tombol **"Lihat Hasil Inspeksi"** yang mengarah ke [`/results/{listing}`](/results/{listing}).
5. Buka halaman hasil inspeksi untuk melihat ringkasan item penilaian (eksterior, interior, mesin, kaki-kaki) dan foto-foto kondisi fisik kendaraan dengan metadata kamera & timestamp.

---

### Menit 0:45 - 1:30: Pembeli Mengajukan Inspeksi & Simulasi Pembayaran QRIS
1. Login sebagai **Pembeli** (`pembeli@demo.id`).
2. Masuk kembali ke katalog [`/listings`](/listings) dan pilih kendaraan yang berstatus belum diinspeksi (misalnya **Daihatsu Sigra 2021** atau **Yamaha Aerox 155**).
3. Klik tombol **"Ajukan Inspeksi Kendaraan"** yang mengarah ke rute pengajuan [`/jobs/request/{listing}`](/jobs/request/{listing}).
4. Konfirmasi permintaan inspeksi. Sistem secara otomatis membuat tagihan pembayaran.
5. Pada halaman pembayaran [`/payments/{id}`](/payments/{id}), klik **"Simulasikan Pembayaran Sukses"**.
6. Status pembayaran berhasil dicatat secara idempoten, dan permintaan inspeksi berpindah status ke `paid` (terbuka di bursa job inspektor).

---

### Menit 1:30 - 2:30: Inspektor Mengambil Job, Mengisi Checklist, & Mengirim Hasil
1. Buka browser baru (atau incognito/logout) lalu login sebagai **Inspektor** (`inspektor@demo.id`).
2. Akses antrean job inspeksi di [`/jobs`](/jobs) atau [`/jobs/open`](/jobs/open).
3. Lihat daftar pekerjaan terbuka (status `paid`). Klik salah satu job, lalu klik **"Ambil Pekerjaan Ini"**.
4. Masuk ke halaman pengisian hasil inspeksi di [`/jobs/{job}/fill`](/jobs/{job}/fill).
5. Tunjukkan pengisian form:
   - Memilih kondisi tiap komponen (Baik, Cukup, Buruk).
   - Menambahkan catatan kerusakan/keausan.
   - Mengunggah foto bukti fisik kamera.
6. Klik **"Kirim Hasil Inspeksi"**. Sistem menghitung skor total secara otomatis menggunakan `ScoreCalculator`, menentukan rekomendasi, membukukan komisi inspeksi ke `ledger_entries`, dan memperbarui status listing menjadi `is_inspected = true`.

---

### Menit 2:30 - 3:15: Penjual/Pembeli Membuka Hasil Berbayar & Mengajukan Penawaran
1. Login kembali sebagai **Pembeli** (`pembeli@demo.id`).
2. Kunjungi unit kendaraan yang hasil inspeksinya telah siap di [`/results/{listing}`](/results/{listing}).
3. Jika unit diinspeksi oleh pihak ketiga, tunjukkan fitur **"Buka Akses Laporan Penuh"** (Access Fee) di mana pembeli lain dapat membayar untuk membuka laporan lengkap.
4. Klik tombol **"Ajukan Penawaran / Beli"** di [`/transactions/offer/{listing}`](/transactions/offer/{listing}).
5. Masukkan harga penawaran dan catatan pembeli, lalu kirim penawaran. Transaksi masuk status `offered`.

---

### Menit 3:15 - 4:15: Transaksi Pembelian Escrow, Serah Terima, & Ulasan Inspektor (RTG-1)
1. Buka transaksi yang sudah disetujui atau transaksi **Honda HR-V** yang berada pada tahap pembayaran escrow (`paid_held`) di [`/transactions/{id}`](/transactions/{id}).
2. Jelaskan model **Escrow SIMOKAS**: dana pembelian ditahan aman di platform sampai pembeli dan penjual menyetujui serah terima kendaraan fisik.
3. Klik konfirmasi **"Serah Terima Unit Selesai"**. Transaksi berpindah status menjadi `completed`, dan bagi hasil komisi transaksi dicatat ke ledger.
4. Pada transaksi yang telah `completed` (dapat dilihat langsung pada contoh transaksi **Honda Brio**):
   - Formulir ulasan inspektor **RTG-1** muncul otomatis khusus untuk pembeli.
   - Berikan nilai rating bintang (1 sampai 5) dan ulasan pengalaman inspeksi (maks. 500 karakter).
   - Klik **"Kirim Ulasan"**.
   - Tunjukkan bahwa ulasan langsung tersimpan, idempoten (tidak dapat dikirim ulang), dan rata-rata rating inspektor (`rating_avg` & `rating_count`) dihitung ulang seketika dengan penguncian baris (`lockForUpdate`).

---

### Menit 4:15 - 5:00: Panel Admin (Antrean Moderasi, Verifikasi Inspektor, Audit Log & Rating)
1. Login sebagai **Admin** (`admin@demo.id`).
2. Kunjungi antrean moderasi terpadu di [`/admin/queue`](/admin/queue) untuk melihat iklan pending dan pendaftaran inspektor.
3. Kunjungi halaman verifikasi inspektor di [`/admin/inspectors`](/admin/inspectors):
   - Tinjau profil **Eko Prasetyo** (`inspektor_pending@demo.id`) beserta dokumen KTP & Portofolio yang terunggah aman.
   - Admin dapat menyetujui atau menolak dengan catatan alasan.
4. Kunjungi moderasi ulasan & rating di [`/admin/ratings`](/admin/ratings):
   - Lihat daftar ulasan transaksi completed.
   - Klik tombol **"Sembunyikan"** pada ulasan yang melanggar. Status berubah menjadi `is_hidden = true`, rating inspektor dihitung ulang tanpa ulasan tersebut, dan tindakan tercatat ke [`/admin/audit-logs`](/admin/audit-logs).
   - Klik **"Tampilkan Kembali"** untuk mengembalikan status ulasan.
5. Presentasi demo 5 menit selesai dengan seluruh siklus end-to-end terverifikasi sempurna.
