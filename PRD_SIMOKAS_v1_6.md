# PRD — Aplikasi SIMOKAS

**Versi:** 1.6

**Status:** Draft, diperbarui 6 Oktober 2026 (keputusan flow D-01 sampai D-06 disalin)

**Platform:** Web responsif (mobile-first)

**Konteks:** Lomba pengembangan web, tim 3 orang, dengan rencana jangka panjang menjadi produk SaaS

**Target pengguna:** Pembeli, penjual, inspektor kendaraan, dan admin

**Prioritas produk:** Alur jual-beli kendaraan bekas yang tepercaya, ringan di perangkat murah, tampilan modern minimalis, dan siap didemokan tanpa error.

### Cara membaca dokumen ini

| Penanda | Arti |
| --- | --- |
| **P0** | Wajib selesai untuk deploy 8 Oktober |
| **P1** | Dikerjakan setelah P0, selesai sebelum pengumpulan web 12 Oktober |
| **P2** | Setelah lomba atau jika waktu tersisa |
| **[ASUMSI]** | Belum dikonfirmasi tim. Nilai yang dipakai adalah rekomendasi penyusun |
| **[OPEN]** | Pertanyaan terbuka, dikumpulkan di Bagian 26 |

### Riwayat perubahan

| Versi | Perubahan |
| --- | --- |
| 1.0 | Draft awal dari wawancara 2 putaran |
| 1.1 | Klarifikasi tim: chat real-time masuk lingkup (P1). Tarif inspeksi Rp15.000 (motor) dan Rp50.000 (mobil), berlaku sama untuk inspeksi baru maupun membuka hasil yang sudah ada. Komisi inspektor dari transaksi di platform 3%. Rating hanya setelah pembeli deal melalui aplikasi. Pembayaran tetap simulasi. Notifikasi hanya di web. Mode gelap ditunda |
| 1.2–1.3 | Tidak tercatat pada draf sebelumnya (nomor versi pada nama file dan isi dokumen tidak sinkron) |
| 1.4 | Penyusunan ulang agar konsisten dan mudah dipakai tim: format tabel dan daftar diperbaiki, istilah uang diseragamkan, tanggal rencana disesuaikan dengan 5 Oktober, celah aturan bisnis ditutup (konflik kepentingan inspektor, kedaluwarsa tagihan, pembulatan, nomor rangka), spesifikasi fungsional transaksi, chat, rating, dan notifikasi ditambahkan, serta endpoint API yang kurang dilengkapi. Rincian ada di lampiran "Catatan Perubahan" pada akhir dokumen |
| 1.5 | Stack diganti dari Next.js + Supabase ke **Laravel + MySQL** (hosting Hostinger Cloud Startup). Bagian 6.2, 13.13, 17, 18, 19, 20.4, 24, 25, dan 26 disesuaikan. Row Level Security diganti Policy/Gate Laravel, Supabase Realtime diganti Pusher (cadangan: polling), indeks unik parsial diganti kolom virtual. Rincian di lampiran E |
| 1.6 | Sinkronisasi keputusan D-01–D-06, komisi transaksi, status, kedaluwarsa, model data, dan panduan inspektor. Rincian di lampiran F |

---

## 1. Ringkasan Produk

SIMOKAS adalah aplikasi web yang menjembatani **penjual, pembeli, dan petugas inspeksi** dalam jual beli mobil dan motor bekas. Penjual memasang penawaran, pembeli dapat meminta inspeksi independen atas kendaraan yang diminati, dan inspektor bersertifikat mengambil pekerjaan inspeksi itu melalui portal *open job*.

Hasil inspeksi disimpan sebagai laporan terstruktur di platform. Listing yang sudah diinspeksi menampilkan lencana **"Sudah Diinspeksi"** secara publik, sedangkan rincian hasilnya terkunci. Pemilik kendaraan dan inspektor dapat melihatnya, sedangkan pihak lain harus membayar biaya akses. Setiap kali hasil diakses, inspektor memperoleh pendapatan, sehingga satu inspeksi bisa menghasilkan pendapatan berulang selama kendaraan belum terjual.

Pada versi lomba, seluruh pembayaran berupa **simulasi** (tanpa uang sungguhan), dan cakupan wilayah awal adalah **Kota Pekanbaru**.

---

## 2. Latar Belakang dan Masalah

Jual beli kendaraan bekas di marketplace konvensional umumnya hanya mengandalkan media sosial dan platform iklan. Ada dua masalah utama:

1. **Kualitas barang tidak terjamin.** Pembeli awam sulit menilai kondisi mesin, kaki-kaki, kelistrikan, dokumen, atau riwayat tabrak/banjir. Mereka bergantung pada klaim penjual.
2. **Tidak ada platform resmi bagi teknisi.** Mekanik berpengalaman dan bersertifikat tidak punya jalur terstruktur untuk mendapat pekerjaan inspeksi sampingan dengan tarif yang jelas dan reputasi yang terukur.

SIMOKAS menyelesaikan keduanya dalam satu ekosistem: pembeli mendapat laporan kondisi yang objektif dengan biaya terjangkau (Rp15.000 untuk motor dan Rp50.000 untuk mobil), dan teknisi mendapat pasar pekerjaan beserta sistem rating.

**Pembeda utama dibanding OLX atau Mobil123:**

* *Open job* untuk inspektor independen, bukan inspeksi oleh pihak penjual.
* Biaya inspeksi sangat terjangkau.
* Hasil inspeksi terstruktur, berfoto, dan bertimestamp, dengan akses berbayar yang adil bagi inspektor.
* Rating inspektor yang memengaruhi kredibilitas dan peringkat.

---

## 3. Tujuan Produk dan Metrik Keberhasilan

### 3.1 Tujuan Produk

1. Memberi pembeli awam jaminan kualitas kendaraan sebelum membeli.
2. Membuka peluang kerja sampingan bagi teknisi bersertifikat melalui portal *open job*.
3. Memudahkan penjual memasang penawaran dan membuktikan kondisi kendaraannya.
4. Mencatat seluruh alur dari listing, inspeksi, pembayaran, sampai transaksi dalam satu platform.
5. Memberi admin kendali atas validasi listing, verifikasi inspektor, dan pengaturan tarif.

### 3.2 Metrik Keberhasilan Versi Lomba [ASUMSI]

Definisi sukses belum dikonfirmasi tim. Usulan berikut dipetakan ke bobot penilaian lomba.

| Kriteria lomba (bobot) | Indikator keberhasilan |
| --- | --- |
| Fungsionalitas (20%) | Satu alur penuh berjalan tanpa error di lingkungan produksi: daftar → posting → validasi admin → ajukan inspeksi → bayar (simulasi) → inspektor mengambil → input hasil → hasil terlihat |
| UI/UX (15%) | Orang baru menyelesaikan alur utama tanpa bantuan dalam kurang dari 5 menit |
| Responsivitas dan Performa (10%) | Lighthouse mobile Performance ≥ 80 dan Accessibility ≥ 90 pada halaman daftar barang dan detail listing |
| Kualitas Teknis (15%) | Akses data dibatasi per role di level database, validasi input di server, tidak ada kebocoran lokasi persis atau data inspektor |
| Inovasi dan Solusi (20%) | Model akses hasil berbayar dan *open job* inspektor dapat dijelaskan dan didemokan |
| Presentasi, Demo, Tanya Jawab (20%) | Skrip demo 5 menit dan akun demo untuk tiap role siap, dengan data contoh yang realistis |

### 3.3 Metrik Keberhasilan Pilot di Pekanbaru [ASUMSI]

Hanya relevan bila SIMOKAS dilanjutkan setelah lomba.

| Metrik | Target 1 bulan | Target 6 bulan |
| --- | --- | --- |
| Inspektor terverifikasi | 10 | 50 |
| Listing aktif | 30 | 300 |
| Inspeksi selesai | 15 | 200 per bulan |
| Listing yang meminta inspeksi | 15% | 25% |
| Rating rata-rata inspektor | ≥ 4,0 | ≥ 4,3 |
| Waktu dari permintaan inspeksi sampai job diambil | < 24 jam | < 12 jam |

Hal-hal yang bukan tujuan produk (non-goals) dicantumkan di Bagian 7.2.

---

## 4. Stakeholder, Tim, dan Timeline

### 4.1 Stakeholder

| Pihak | Peran |
| --- | --- |
| Tim pengembang (3 orang) | Pemilik produk sekaligus pelaksana. Pembagian peran: [OPEN] |
| Tim IT | Pemberi keputusan teknis |
| Panitia/juri lomba | Penilai berdasarkan kriteria pada Bagian 3.2 |
| Calon pengguna di Pekanbaru | Sumber validasi kebutuhan (pembeli, penjual, mekanik) |

**Usulan pembagian peran** [ASUMSI]: (A) frontend dan UI, (B) backend, database, dan API, (C) fullstack untuk formulir inspeksi, QA, dan dokumentasi.

### 4.2 Timeline Lomba

| Tanggal (2026) | Kegiatan |
| --- | --- |
| 23 September – 4 Oktober | Pendaftaran (sudah ditutup) |
| **8 Oktober** | **Target deploy internal (P0)** |
| **12 Oktober** | **Pengumpulan web** |
| 13 – 14 Oktober | Penilaian |
| 15 Oktober | Pengumuman finalis |
| 16 Oktober | Presentasi finalis web |
| 17 Oktober | Pengumuman pemenang |

Rencana pengerjaan harian ada di Bagian 23.2.

---

## 5. Target Pengguna

Target pasar mencakup generasi boomer sampai gen Z, dengan kemampuan teknologi yang sangat beragam. Karena itu antarmuka harus sederhana, dengan teks yang jelas dan tombol besar.

### 5.1 Pembeli

Calon pembeli kendaraan bekas, banyak di antaranya awam soal kondisi teknis.

Kebutuhan:

* Mencari kendaraan dengan filter yang jelas.
* Mengetahui kondisi sebenarnya tanpa harus paham mesin.
* Meminta inspeksi dengan biaya murah.
* Bertransaksi dengan aman dan menilai inspektor.

Tugas utama: melihat daftar barang, mengajukan atau melihat hasil inspeksi (opsional), mengobrol dengan penjual, dan bertransaksi.

### 5.2 Penjual

Pemilik kendaraan, baik perorangan maupun pelapak kecil.

Kebutuhan:

* Memasang penawaran dengan cepat.
* Membuktikan kendaraannya layak melalui lencana inspeksi.
* Mengatur waktu dan tempat inspeksi.

Tugas utama: membuat dan mengedit penawaran, melihat hasil inspeksi atas kendaraannya, dan bertransaksi.

### 5.3 Inspektor

Teknisi atau mekanik bersertifikat dan berpengalaman.

Kebutuhan:

* Pekerjaan sampingan dengan tarif jelas.
* Reputasi yang terukur melalui rating.
* Formulir inspeksi yang cepat diisi di lapangan, di HP.

Tugas utama: melihat daftar pengajuan inspeksi, memilih job, berkomunikasi dengan penjual, menginput hasil inspeksi, dan menerima pembayaran.

### 5.4 Admin

Tim pengembang atau operator platform.

Tugas utama: memvalidasi listing, memverifikasi inspektor, mengatur tarif dan persentase komisi, memantau transaksi, dan mengelola pengguna.

### 5.5 Perangkat dan Kondisi Pemakaian

* Semua perangkat dengan browser modern (HP, tablet, laptop).
* Inspektor memakai HP di lapangan, sehingga formulir inspeksi dan kamera harus nyaman di layar kecil.
* Kondisi lapangan inspektor (sinyal, cuaca) belum diketahui [OPEN]. Aplikasi tidak offline-first, sehingga koneksi internet wajib.
* Bahasa: Indonesia. Tidak ada kebutuhan aksesibilitas khusus, tetapi standar dasar tetap dipenuhi (kontras, ukuran teks, label form).

---

## 6. Platform dan Batasan Teknis

### 6.1 Platform

* Aplikasi web responsif, dapat diakses dari semua perangkat melalui browser.
* Tidak ada aplikasi mobile native (APK/iOS).
* Tidak ada mode offline. Seluruh fitur memerlukan internet.
* Distribusi melalui hosting dan domain yang sudah dimiliki tim: paket **Hostinger Cloud Startup** (PHP, MySQL, SSH, cron lewat hPanel). Kemampuan paket yang belum terverifikasi dicatat di Catatan hosting pada 6.2 [OPEN].

### 6.2 Stack yang Direkomendasikan

Tim menyerahkan pemilihan stack kepada rekomendasi penyusun. Prioritas tim adalah performa ringan dan tampilan menarik.

| Lapisan | Pilihan | Alasan |
| --- | --- | --- |
| Framework | Laravel 13 (PHP 8.3) | Berjalan di hosting PHP, auth, validasi, policy, antrean, dan scheduler sudah bawaan |
| Frontend | Blade + Livewire + Tailwind CSS | Satu proyek tanpa API dan build SPA terpisah, cepat dikerjakan dua orang, ringan di perangkat murah |
| Database | MySQL/MariaDB (Hostinger) | Tersedia di paket hosting. PostgreSQL tidak tersedia di paket Cloud Hostinger, dan PostgreSQL eksternal (Neon) menambah latensi dan titik gagal |
| Autentikasi | Laravel Breeze (email + password), role di tabel `users` | Tidak perlu membangun login dari nol |
| Hak akses | Policy, Gate, dan middleware role | Pengganti Row Level Security. Seluruh akses data lewat Eloquent di server |
| Penyimpanan foto dan dokumen | Filesystem Laravel, disk privat + tautan bertanda tangan sementara | Dokumen KTP dan foto inspeksi tidak dapat diakses publik |
| Peta | Leaflet + OpenStreetMap | Gratis, tanpa kartu kredit atau API key |
| Pembayaran | Modul simulasi internal (*mock gateway*) | Sesuai keputusan tim. Midtrans Sandbox bisa menjadi jalur nyata kelak |
| Chat dan notifikasi real-time | Laravel Echo + Pusher (paket gratis). Cadangan: polling Livewire tiap 3–5 detik | Hostinger Cloud kemungkinan tidak dapat menjalankan proses background untuk Laravel Reverb |
| Tugas terjadwal | Laravel Scheduler lewat cron hPanel | Kedaluwarsa tagihan 30 menit dan penandaan job "perlu tindakan" 48 jam |
| Hosting | Hostinger Cloud Startup (domain yang sudah dimiliki) | Sudah dimiliki tim |

> **Catatan hosting.** Kemampuan paket Cloud Startup belum diverifikasi langsung. Yang perlu dicek di hPanel sebelum 8 Oktober: versi PHP 8.3, akses SSH dan Composer, cron job berjalan tiap menit untuk `schedule:run`, dan koneksi keluar ke Pusher. Proses yang harus terus hidup (Laravel Reverb, queue worker) tidak diandalkan. Antrean memakai driver `database` yang diproses oleh scheduler, atau pekerjaan dijalankan sinkron.

### 6.3 Batasan

* Hanya mobil dan motor.
* Wilayah awal hanya Pekanbaru.
* Satu bahasa (Indonesia).
* Mode gelap ditunda (P2), tetapi warna dibuat berbasis token agar mudah ditambahkan.
* Browser yang didukung: dua versi terakhir Chrome, Edge, Firefox, Safari, serta Chrome dan Safari di HP.

---

## 7. Ruang Lingkup Produk

### 7.1 In Scope

| Fitur | Pembeli/Penjual | Inspektor | Admin | Prioritas |
| --- | :---: | :---: | :---: | :---: |
| Registrasi dan login (email + password), reset lewat tautan email | ✔ | ✔ | ✔ | P0 |
| Daftar barang dengan pencarian, filter, dan urutan | ✔ | | | P0 |
| Detail listing dengan lencana "Sudah Diinspeksi" | ✔ | | | P0 |
| Tambah dan edit penawaran (penjual) | ✔ | | | P0 |
| Validasi listing oleh admin | | | ✔ | P0 |
| Daftar dan verifikasi inspektor oleh admin | | ✔ | ✔ | P0 |
| Ajukan inspeksi (pembeli atau penjual) | ✔ | | | P0 |
| Pembayaran simulasi | ✔ | | | P0 |
| Daftar job inspeksi, ambil job (1 job : 1 inspektor) | | ✔ | | P0 |
| Input hasil inspeksi berbasis template (mobil dan motor berbeda), foto kamera + timestamp | | ✔ | | P0 |
| Lihat hasil inspeksi (terkunci kecuali pemilik, inspektor, atau yang sudah membayar akses) | ✔ | ✔ | ✔ | P0 |
| Dashboard per role | ✔ | ✔ | ✔ | P0 |
| Pengaturan akun | ✔ | ✔ | ✔ | P0 |
| Atur tarif, komisi, dan template inspeksi | | | ✔ | P0 |
| Notifikasi dalam web (in-app, real-time) | ✔ | ✔ | ✔ | P1 |
| Transaksi jual beli di dalam platform (pembayaran simulasi, dana ditahan) | ✔ | | ✔ | P1 |
| Chat **real-time** dalam aplikasi antara pembeli–penjual dan inspektor–penjual | ✔ | ✔ | | P1 |
| Rating inspektor dan peringkat (hanya setelah pembeli deal melalui aplikasi) | ✔ | ✔ | | P1 |
| Halaman kebijakan privasi dan syarat ketentuan (disclaimer) | ✔ | ✔ | ✔ | P1 |
| Riwayat transaksi dan riwayat hasil inspeksi lengkap | ✔ | ✔ | ✔ | P1 |
| Mode gelap | ✔ | ✔ | ✔ | P2 |
| Audit log dan analitik pemakaian | | | ✔ | P2 |
| Pelaporan atau sanggahan hasil inspeksi | ✔ | ✔ | ✔ | P2 |
| Batasan job berdasarkan tingkat rating inspektor | | ✔ | ✔ | P2 |

### 7.2 Out of Scope

SIMOKAS **bukan** marketplace iklan umum, bukan layanan pengiriman kendaraan, dan bukan lembaga pembiayaan. Fitur berikut tidak masuk versi lomba:

1. Pembayaran uang sungguhan dan payment gateway produksi (versi lomba memakai simulasi).
2. Pengiriman kendaraan, kredit, leasing, dan asuransi.
3. Aplikasi mobile native.
4. Jenis kendaraan lain (truk, sepeda listrik).
5. Wilayah di luar Pekanbaru.
6. Ekspor PDF dan cetak laporan. Hasil inspeksi hanya dilihat di web.
7. Arsitektur multi-tenant untuk SaaS (dicatat di roadmap).
8. Notifikasi email, WhatsApp, dan push di luar web.

### 7.3 Keputusan Tim yang Sudah Ditetapkan

Tim memandang **P0 dan P1 sebagai MVP lomba**. P0 menjadi target deploy 8 Oktober, dan P1 menyusul sebelum pengumpulan 12 Oktober.

| Isu | Keputusan |
| --- | --- |
| Rating | Hanya setelah pembeli deal melalui aplikasi (P1) |
| Pembayaran di dalam aplikasi | Simulasi untuk MVP, termasuk pembayaran harga kendaraan (P1) |
| Chat | Real-time dalam aplikasi (P1) |
| Notifikasi | Hanya di dalam web (P1) |
| Mode gelap | Ditunda (P2) |
| Tarif inspeksi | Motor Rp15.000, mobil Rp50.000, berlaku sama untuk inspeksi baru maupun membuka hasil yang sudah ada |
| Komisi inspektor dari transaksi di platform | 3% dari harga jual |

---

## 8. Aturan Bisnis Utama

Bagian ini memuat aturan uang dan akses yang menjadi inti inovasi SIMOKAS. Tarif dan persentase di bawah sudah ditetapkan tim pada v1.1 dan dapat diubah admin. Hal yang masih berupa dugaan ditandai **[ASUMSI]**.

### 8.1 Biaya dan Komisi

| Komponen | Aturan | Status |
| --- | --- | --- |
| Tarif inspeksi | **Rp15.000 untuk motor, Rp50.000 untuk mobil.** Tetap per jenis kendaraan, diatur admin | Ditetapkan tim |
| Biaya akses hasil | Sama dengan tarif inspeksi kendaraan itu. Berlaku untuk kendaraan yang **belum** diinspeksi (membayar inspeksi baru) maupun yang **sudah** diinspeksi (membayar untuk membuka hasil) | Ditetapkan tim |
| Pembayar | Pihak yang mengajukan inspeksi (pembeli atau penjual), atau pihak lain yang membuka hasil | Ditetapkan tim |
| Komisi platform | **10%** dari setiap pembayaran tarif inspeksi atau biaya akses. Sisanya menjadi pendapatan inspektor | Ditetapkan tim |
| Komisi transaksi inspektor | **3%** dari harga transaksi yang disetujui, jika transaksi terjadi di dalam platform dan listing memiliki hasil inspeksi | Ditetapkan tim |
| Potongan platform atas komisi transaksi | Komisi platform 10% juga berlaku (3% bruto menjadi 2,7% bersih) | [ASUMSI] |
| Penanggung komisi transaksi | Dipotong dari dana penjual. Pembeli membayar harga transaksi yang disepakati | [ASUMSI] |
| Pungutan lain dari penjual atau pembeli | Tidak ada selain 10% dari pendapatan inspektor | [ASUMSI] [OPEN] |
| Penerima komisi transaksi | Inspektor pembuat hasil inspeksi terbaru yang berlaku pada listing itu. Tanpa inspeksi, tidak ada komisi | [ASUMSI] [OPEN] |
| Pembulatan | Seluruh nilai dalam rupiah bulat. Komisi platform dihitung dengan pembulatan ke rupiah terdekat, dan sisanya menjadi bagian inspektor sehingga jumlah selalu sama dengan nilai pembayaran | [ASUMSI] |

**Contoh perhitungan [ASUMSI]:**

| Kasus | Pembayaran | Komisi platform | Inspektor |
| --- | --- | --- | --- |
| Mobil, pembeli A mengajukan inspeksi | Rp50.000 | Rp5.000 | Rp45.000 (cair setelah job selesai) |
| Motor, pembeli B membuka hasil yang sudah ada | Rp15.000 | Rp1.500 | Rp13.500 |
| Transaksi mobil Rp100.000.000 lewat platform | Komisi transaksi 3% = Rp3.000.000 | Rp300.000 (10% dari komisi transaksi) | Rp2.700.000. Penjual menerima Rp97.000.000 |

> Karena komisi transaksi 3% jauh lebih besar daripada tarif inspeksi, penjual harus melihat rincian potongan sebelum menyetujui pembelian (lihat 8.6).

### 8.2 Aturan Akses Hasil Inspeksi

1. Listing yang sudah diinspeksi menampilkan lencana **"Sudah Diinspeksi"** kepada semua orang, lengkap dengan tanggal inspeksi dan skor ringkas (opsional, [OPEN]).
2. Rincian hasil **disembunyikan** dari publik.
3. Pihak yang boleh melihat rincian tanpa membayar akses: **pemilik kendaraan**, **inspektor yang membuat laporan**, **pihak yang membayar inspeksi**, dan **admin**.
4. Pihak lain harus membayar biaya akses, yang **sama dengan tarif inspeksi** kendaraan itu. Setelah membayar, akses berlaku selama listing **belum Terjual**.
5. Setelah listing berstatus **Terjual**, akses baru ditutup dan pendapatan akses berhenti. Pihak yang sudah membayar tetap melihat hasil itu di riwayatnya.
6. Setiap pembayaran akses menghasilkan pendapatan bagi inspektor sesuai 8.1.

### 8.3 Aturan Job Inspeksi

1. Satu job hanya dapat diambil **satu inspektor**.
2. Jika inspektor membatalkan atau tidak datang, job kembali ke daftar dengan status Dibayar.
3. Satu kendaraan hanya boleh memiliki **satu job aktif** pada satu waktu. Job aktif adalah job berstatus Diajukan sampai Hasil Diinput.
4. Penjual menetapkan **opsi waktu dan tempat** inspeksi saat membuat penawaran. Inspektor berkomunikasi dengan penjual (lewat chat) dan menetapkan jadwal final.
5. Inspektor bebas memilih job di mana saja, sesuai kemampuannya (tidak ada batas wilayah di dalam Pekanbaru).
6. Alamat persis dan nomor rangka kendaraan baru terlihat oleh inspektor **setelah job diambil**.
7. **Konflik kepentingan** [ASUMSI]: inspektor tidak dapat mengambil job atas listing miliknya sendiri, dan tidak dapat menjadi pemohon inspeksi atas job yang akan ia kerjakan.
8. Job `paid` yang tidak diambil siapa pun otomatis `cancelled` setelah **7 hari dari `available_since`**, refund **100%**, listing `active`, dan pemohon dinotifikasi. Setelah inspektor membatalkan, penghitung dimulai ulang dari `available_since` baru (D-01).

### 8.4 Pembatalan dan Pengembalian Dana [ASUMSI] [OPEN]

| Kondisi | Aturan |
| --- | --- |
| Dibatalkan pemohon sebelum job diambil | Pengembalian 100% (simulasi) |
| Dibatalkan pemohon setelah diambil, sebelum inspeksi dimulai | Pengembalian 100% |
| Dibatalkan setelah inspeksi dimulai | Tidak ada pengembalian |
| Dibatalkan inspektor | Job kembali ke daftar, dana tetap ditahan. Pembatalan berulang dicatat untuk tinjauan admin |
| Tagihan tidak dibayar dalam 30 menit | Job kedaluwarsa tanpa biaya |

### 8.5 Penyelesaian Job dan Pencairan Fee

Setelah inspektor mengirim hasil, job **Selesai** dan fee cair saat pemohon menekan **"Terima Hasil"**, otomatis setelah **3 hari** berdasarkan `auto_accept_days` yang dapat diubah admin, atau admin menyelesaikannya (D-03). Laporan terkunci setelah dikirim; hanya admin dapat membukanya kembali.

### 8.6 Transaksi Jual Beli di Dalam Platform (P1, simulasi)

1. Pembeli menekan **Ajukan Pembelian** dengan harga.
2. Penjual melihat rincian termasuk komisi transaksi 3% bila ada hasil inspeksi, lalu Setuju atau Tolak.
3. Pengajuan/persetujuan boleh saat listing **Dalam Inspeksi**, tetapi **pembayaran baru dibuka setelah tidak ada job aktif** (D-02).
4. Pembeli membayar (simulasi), dana **Ditahan**, listing Dalam Transaksi.
5. Pembeli **Konfirmasi Serah Terima**; dana diteruskan setelah komisi.
6. Transaksi Selesai, listing Terjual, dan rating terbuka.
7. Komisi transaksi 3% dipotong dari penjual dan platform mengambil 10% darinya **[ASUMSI]**.

#### Pembatalan dan kedaluwarsa transaksi

| Kondisi | Siapa | Aturan | Dana | Listing |
| --- | --- | --- | --- | --- |
| `offered` (belum disetujui) | Pembeli | Batal bebas | - | tetap |
| `offered` | Penjual | Tolak (`rejected`) | - | tetap |
| `accepted` (belum bayar) | Pembeli atau penjual | Batal bebas, dicatat | - | kembali ke status sebelum transaksi |
| `paid_held` | Penjual | Batal sepihak diperbolehkan, dicatat untuk tinjauan admin | Refund 100% ke pembeli | `active` |
| `paid_held` | Pembeli | Perlu persetujuan penjual **atau** admin | Refund 100% ke pembeli | `active` |
| `paid_held` | Admin | Dapat membatalkan atau menyelesaikan manual | Sesuai keputusan admin | sesuai |
| Semua pembatalan | | Komisi inspektor 3% **tidak** dibayar | | |

Kedaluwarsa **[USULAN]**: `offered` 48 jam → `expired`; `accepted` 24 jam tanpa pembayaran sejak pembayaran dibuka → `expired`; `paid_held` 7 hari tanpa serah terima → **perlu tindakan** admin, tidak otomatis selesai.

Satu listing hanya boleh memiliki satu transaksi berjalan. Contoh Rp100.000.000: komisi Rp3.000.000, platform Rp300.000, inspektor Rp2.700.000, penjual Rp97.000.000.
### 8.7 Rating Inspektor (P1)

* Hanya pembeli yang sudah deal dan menyelesaikan pembelian melalui aplikasi yang dapat memberi rating (ditegaskan tim).
* Skala 1–5 dengan komentar, satu rating per transaksi.
* Rating memengaruhi kredibilitas, peringkat, dan kelak job yang dapat diambil (P2).
* Konsekuensi: inspektor yang inspeksinya tidak berujung penjualan tidak pernah dinilai. Usulan tambahan: pemohon inspeksi juga boleh memberi rating setelah menerima hasil [OPEN].

---

## 9. Struktur Halaman

### 9.1 Publik (tanpa login)

| Halaman | Isi |
| --- | --- |
| Beranda | Hero, cara kerja, listing unggulan, ajakan menjadi inspektor |
| Daftar barang | Kartu listing, pencarian, filter, urutan |
| Detail listing | Foto, spesifikasi, harga, area (bukan alamat persis), lencana inspeksi, tombol aksi |
| Masuk / Daftar | Form login dan registrasi |
| Cara Kerja dan Bantuan/FAQ | Penjelasan singkat alur untuk pengguna awam dan juri |
| Panduan Inspektor | Panduan satu halaman untuk pendaftaran, job, inspeksi, foto, fee, dan pembatalan |
| Kebijakan privasi, Syarat ketentuan | Konten statis (P1) |

### 9.2 Pembeli dan Penjual

| Halaman | Isi |
| --- | --- |
| Dashboard | Inspeksi berjalan, transaksi terakhir, listing aktif (penjual) |
| Listing saya | Daftar, status, edit |
| Buat / edit penawaran | Form listing, unggah foto, pilih titik lokasi, opsi waktu inspeksi |
| Ajukan inspeksi | Konfirmasi, pembayaran simulasi |
| Hasil inspeksi | Laporan terstruktur (terkunci atau terbuka) |
| Transaksi | Daftar dan detail transaksi (P1) |
| Chat | Daftar percakapan dan ruang obrolan (P1) |
| Notifikasi | Daftar notifikasi (P1) |
| Pengaturan akun | Profil, ubah password |

### 9.3 Inspektor

| Halaman | Isi |
| --- | --- |
| Dashboard | Job tersedia, job saya, pendapatan, rating |
| Daftar job | Job terbuka dengan jenis kendaraan, area, tarif |
| Detail job | Informasi kendaraan, jadwal, chat dengan penjual, aksi |
| Form inspeksi | Checklist berbasis template, foto kamera |
| Riwayat inspeksi | Hasil yang pernah dikirim |
| Pendapatan | Saldo simulasi dan rincian komisi |
| Pengaturan akun | Profil, dokumen verifikasi |

### 9.4 Admin

| Halaman | Isi |
| --- | --- |
| Dashboard | Jumlah pengguna, job, pendapatan, antrean validasi |
| Validasi listing | Setujui atau tolak dengan alasan |
| Verifikasi inspektor | Lihat KTP, sertifikat, CV, portofolio, lalu setujui atau tolak |
| Pengguna | Daftar, blokir, atau aktifkan |
| Job dan transaksi | Pantau, selesaikan manual, atau batalkan |
| Pengaturan | Tarif, biaya akses, persentase komisi, template inspeksi |

---

## 10. Hak Akses

| Aksi | Publik | Pembeli/Penjual | Inspektor | Admin |
| --- | :---: | :---: | :---: | :---: |
| Melihat listing aktif | ✔ | ✔ | ✔ | ✔ |
| Melihat lencana "Sudah Diinspeksi" | ✔ | ✔ | ✔ | ✔ |
| Melihat alamat persis dan nomor rangka | | Pemilik | Job miliknya | ✔ |
| Membuat dan mengedit listing sendiri | | ✔ | | ✔ |
| Menyetujui atau menolak listing | | | | ✔ |
| Mengajukan inspeksi | | ✔ | | |
| Melihat daftar job terbuka | | | ✔ (terverifikasi) | ✔ |
| Mengambil job | | | ✔ (terverifikasi) | |
| Menginput hasil inspeksi | | | Pemilik job | |
| Melihat rincian hasil | | Pemilik, pembayar inspeksi, pembayar akses | Pembuat laporan | ✔ |
| Memberi rating | | Pembeli transaksi selesai | | |
| Memverifikasi inspektor | | | | ✔ |
| Melihat dokumen verifikasi inspektor | | | Pemilik dokumen | ✔ |
| Mengatur tarif, komisi, template | | | | ✔ |
| Mengelola pengguna | | | | ✔ |

**Aturan tambahan:**

* Satu akun pengguna umum dapat berperan sebagai **pembeli sekaligus penjual**.
* Inspektor memakai akun role tersendiri yang diverifikasi admin. **[OPEN] D-06:** membeli, menjual, atau mengajukan inspeksi tetap **dilarang**. Untuk membeli, buat akun pengguna terpisah (email berbeda).
* Akun admin dibuat manual (seed), tidak bisa didaftarkan dari halaman publik.
* Satu akun dapat dipakai di banyak perangkat.
* Inspektor yang belum diverifikasi tidak dapat melihat atau mengambil job.

---

## 11. User Stories

| ID | Sebagai | Saya ingin... | Agar... | Prioritas |
| --- | --- | --- | --- | :---: |
| B1 | Pembeli | mencari dan menyaring kendaraan | cepat menemukan yang sesuai anggaran | P0 |
| B2 | Pembeli | melihat lencana "Sudah Diinspeksi" | tahu mana yang kondisinya sudah diperiksa | P0 |
| B3 | Pembeli | mengajukan inspeksi untuk kendaraan yang saya minati | tidak membeli dengan buta | P0 |
| B4 | Pembeli | membayar biaya akses untuk melihat hasil inspeksi yang sudah ada | menghemat biaya dan waktu | P0 |
| B5 | Pembeli | berkomunikasi dengan penjual di aplikasi | menanyakan detail dan bernegosiasi | P1 |
| B6 | Pembeli | membeli kendaraan lewat platform | transaksi tercatat dan aman | P1 |
| B7 | Pembeli | memberi rating inspektor | membantu pembeli lain menilai inspektor | P1 |
| S1 | Penjual | membuat dan mengedit penawaran | kendaraan saya tampil menarik | P0 |
| S2 | Penjual | menentukan opsi waktu dan tempat inspeksi | inspeksi berjalan sesuai kesiapan saya | P0 |
| S3 | Penjual | mengajukan inspeksi untuk kendaraan sendiri | mendapat lencana dan kepercayaan pembeli | P0 |
| S4 | Penjual | melihat hasil inspeksi atas kendaraan saya | tahu kondisi sebenarnya dan menetapkan harga | P0 |
| S5 | Penjual | mengobrol dengan inspektor dan pembeli | mengatur jadwal dan negosiasi | P1 |
| S6 | Penjual | menerima atau menolak pengajuan beli | mengendalikan penjualan | P1 |
| I1 | Inspektor | mendaftar dan diverifikasi | bisa mulai menerima job | P0 |
| I2 | Inspektor | melihat daftar job terbuka | memilih yang sesuai kemampuan | P0 |
| I3 | Inspektor | mengambil job | tidak diambil inspektor lain | P0 |
| I4 | Inspektor | menetapkan jadwal inspeksi lewat obrolan dengan penjual | inspeksi terkoordinasi | P1 |
| I5 | Inspektor | mengisi checklist inspeksi dari HP | cepat dan rapi di lapangan | P0 |
| I6 | Inspektor | mengambil foto langsung dari kamera | bukti kuat dan bertimestamp | P0 |
| I7 | Inspektor | melihat pendapatan dan komisi berulang | tahu penghasilan saya | P0 |
| I8 | Inspektor | memiliki rating dan peringkat | mendapat kredibilitas dan lebih banyak job | P1 |
| A1 | Admin | memvalidasi listing sebelum tampil | mencegah listing palsu dan penipuan | P0 |
| A2 | Admin | memverifikasi dokumen inspektor | hanya inspektor kompeten yang bekerja | P0 |
| A3 | Admin | mengatur tarif, komisi, dan template inspeksi | menyesuaikan bisnis tanpa mengubah kode | P0 |
| A4 | Admin | melihat ringkasan pengguna, job, dan pendapatan | memantau kesehatan platform | P0 |
| A5 | Admin | menyelesaikan atau membatalkan job secara manual | menangani kasus macet | P0 |
| A6 | Admin | memblokir pengguna bermasalah | menjaga keamanan platform | P1 |

---

## 12. User Flow

### 12.1 Registrasi dan Login

1. Pengguna membuka halaman Daftar dan memilih jenis akun: **Pengguna (pembeli/penjual)** atau **Inspektor**.
2. Mengisi nama, email, nomor HP, dan password.
3. Sistem membuat akun dan masuk ke dashboard sesuai role.
4. Untuk inspektor, setelah mendaftar sistem meminta unggah dokumen (KTP, sertifikat pendukung, CV, portofolio). Status akun **Menunggu Verifikasi** dan fitur job terkunci sampai admin menyetujui.
5. Lupa password: pengguna meminta tautan reset melalui email, lalu membuat password baru.

### 12.2 Membuat Penawaran (Penjual)

1. Penjual menekan **Tambah Barang**.
2. Mengisi data kendaraan, harga, deskripsi, dan mengunggah foto (3–10).
3. Menandai titik lokasi di peta dan mengisi alamat.
4. Mengisi **opsi waktu dan tempat inspeksi**.
5. Mengirim. Status listing: **Menunggu Validasi**.
6. Admin menyetujui (listing menjadi **Aktif**) atau menolak dengan alasan (**Ditolak**, penjual dapat memperbaiki dan mengirim ulang).

### 12.3 Inspeksi dari Awal sampai Akhir

1. Pembeli atau penjual membuka detail listing dan menekan **Ajukan Inspeksi**.
2. Sistem menampilkan rincian biaya dan tombol **Bayar (Simulasi)**. Tagihan berlaku 30 menit.
3. Setelah pembayaran sukses, job berstatus **Dibayar** dan muncul di daftar job inspektor. Listing berstatus **Dalam Inspeksi**.
4. Inspektor terverifikasi memilih job, lalu menekan **Ambil Job**. Status: **Diambil**. Alamat persis dan nomor rangka kini terlihat oleh inspektor.
5. Inspektor dan penjual berdiskusi di chat, lalu inspektor menetapkan jadwal final. Status: **Dijadwalkan**.
6. Pada hari inspeksi inspektor menekan **Mulai Inspeksi**. Status: **Sedang Diinspeksi**.
7. Inspektor mengisi checklist dan mengambil foto dari kamera, lalu menekan **Kirim Hasil**. Status: **Hasil Diinput**.
8. Pemohon membuka hasil dan menekan **Terima Hasil**. Status: **Selesai**. Fee masuk ke saldo inspektor (setelah komisi platform 10%).
9. Listing menampilkan lencana **Sudah Diinspeksi**, dan listing kembali **Aktif**.

### 12.4 Membuka Hasil Inspeksi oleh Pihak Lain

1. Pembeli lain membuka listing berlencana dan menekan **Lihat Hasil Inspeksi**.
2. Sistem memeriksa hak akses. Jika belum berhak, muncul rincian biaya akses.
3. Pembeli membayar (simulasi). Sistem mencatat akses dan membagi pendapatan ke inspektor dan platform.
4. Hasil terbuka dan dapat dilihat selama listing belum Terjual.

### 12.5 Transaksi Jual Beli (P1)

Sesuai Bagian 8.6: Ajukan Pembelian, Penjual Setuju, Pembeli Bayar (dana ditahan), Konfirmasi Serah Terima, Selesai, lalu Rating.

### 12.6 Chat (P1)

1. Dari detail listing, pembeli menekan **Chat Penjual**. Dari detail job, inspektor menekan **Chat Penjual**.
2. Sistem membuat percakapan unik per pasangan peserta dan per konteks (listing atau job).
3. Pesan teks terkirim dan tampil **real-time** bagi lawan bicara tanpa memuat ulang halaman. Penanda "belum dibaca" di menu juga diperbarui real-time.
4. Jika koneksi terputus, sistem menyambung ulang otomatis dan mengambil pesan yang terlewat.

### 12.7 Verifikasi Inspektor oleh Admin

1. Admin membuka antrean Verifikasi Inspektor.
2. Meninjau dokumen. Pilihan: **Setujui** atau **Tolak** (wajib alasan).
3. Jika disetujui, role aktif dan inspektor dapat melihat job. Jika ditolak, inspektor dapat memperbaiki dokumen dan mengajukan ulang.

### 12.8 Validasi Listing oleh Admin

1. Admin membuka antrean Validasi Listing dan memeriksa data serta foto.
2. Admin memilih **Setujui** atau **Tolak** dengan alasan.
3. Penolakan memicu notifikasi ke penjual (P1).

---

## 13. Kebutuhan Fungsional

### 13.1 Registrasi dan Login (P0)

Field registrasi pengguna umum:

| Field | Tipe | Wajib | Aturan |
| --- | --- | :---: | --- |
| Nama lengkap | Teks | Ya | 3–60 karakter |
| Email | Email | Ya | Unik, format valid |
| Nomor HP | Teks | Ya | Format Indonesia (08... atau +62...), 9–14 digit |
| Password | Teks | Ya | Minimal 8 karakter, kombinasi huruf dan angka |
| Setuju syarat ketentuan | Checkbox | Ya | Wajib dicentang (P1) |

Field tambahan inspektor:

| Field | Tipe | Wajib | Aturan |
| --- | --- | :---: | --- |
| Jenis keahlian | Pilihan ganda | Ya | Mobil, Motor, atau keduanya |
| Pengalaman (tahun) | Angka | Ya | 0–50 |
| Foto KTP | Berkas | Ya | JPG/PNG/PDF, maksimal 2 MB |
| Sertifikat pendukung | Berkas | Ya | Maksimal 3 berkas, 2 MB per berkas |
| CV | Berkas | Ya | PDF, maksimal 2 MB |
| Portofolio | Berkas atau tautan | Ya | Berkas maksimal 2 MB atau URL |

Aturan:

* Dokumen verifikasi tersimpan di penyimpanan **privat**. Hanya pemilik dan admin yang dapat membukanya.
* Setelah login, pengguna diarahkan ke dashboard sesuai role.
* Login gagal menampilkan pesan generik tanpa menyebut email atau password yang salah.
* Sesi berakhir otomatis setelah periode tidak aktif [ASUMSI: 7 hari].

### 13.2 Daftar Barang dan Detail Listing (P0)

Daftar barang:

* Menampilkan hanya listing berstatus **Aktif**, **Dalam Inspeksi**, atau **Dalam Transaksi**.
* Pagination 12 kartu per halaman, foto dimuat malas (*lazy load*).
* Kartu menampilkan foto utama, judul, harga, tahun, kilometer, area, dan lencana inspeksi.
* Filter dan pencarian: kata kunci, jenis (mobil/motor), merek, rentang harga, rentang tahun, area/kecamatan, status inspeksi (sudah/belum).
* Urutan: terbaru, harga termurah, harga termahal, kilometer terendah.

Detail listing:

* Galeri foto, spesifikasi, deskripsi, harga (dan penanda bisa nego).
* Peta **perkiraan area** tanpa titik persis.
* Lencana **Sudah Diinspeksi** dengan tanggal inspeksi.
* Tombol aksi sesuai peran: Ajukan Inspeksi, Lihat Hasil, Chat Penjual, Ajukan Pembelian.

### 13.3 Tambah dan Edit Penawaran (P0)

| Field | Tipe | Wajib | Aturan |
| --- | --- | :---: | --- |
| Jenis kendaraan | Pilihan | Ya | Mobil atau Motor |
| Judul | Teks | Ya | 10–100 karakter |
| Merek | Pilihan | Ya | Dari daftar merek [OPEN: isi daftar] |
| Model | Teks | Ya | 1–60 karakter |
| Tahun | Angka | Ya | 1990 sampai tahun berjalan |
| Harga | Angka (Rp) | Ya | > 0, maksimal 12 digit |
| Harga bisa nego | Ya/Tidak | Ya | Default Ya |
| Kilometer | Angka | Ya | 0–999.999 |
| Transmisi | Pilihan | Ya | Manual, Otomatis (motor: Manual, Matic) |
| Bahan bakar | Pilihan | Ya | Bensin, Diesel, Listrik, Hybrid |
| Warna | Teks | Ya | 1–30 karakter |
| Nomor rangka | Teks | Tidak | 17 karakter untuk mobil. Dilihat hanya pemilik, inspektor job, dan admin [ASUMSI] |
| Deskripsi | Teks panjang | Tidak | Maksimal 2.000 karakter |
| Foto | Berkas | Ya | Minimal 3, maksimal 10, maksimal 2 MB per foto, JPG/PNG/WebP |
| Titik lokasi | Peta | Ya | Harus di dalam wilayah Pekanbaru |
| Alamat lengkap | Teks | Ya | Dilihat hanya pemilik, inspektor job, dan admin |
| Opsi waktu inspeksi | Daftar tanggal/jam | Ya | Minimal 1 opsi, tidak di masa lalu |
| Tempat inspeksi | Teks | Ya | Bisa sama dengan alamat atau tempat lain |

Aturan:

* Listing baru berstatus **Menunggu Validasi** dan tidak tampil publik.
* Field **non-inti** (harga, nego, deskripsi, foto, warna, slot dan tempat inspeksi) berlaku langsung.
* Field **inti** (jenis, merek, model, tahun, nomor rangka, kilometer) → `pending_review` dan lencana dicabut (`is_inspected = false`). Hasil lama tetap di riwayat pembayar; lencana kembali setelah inspeksi baru selesai (D-05).
* Field inti terkunci saat job aktif/transaksi; saat `in_transaction`, seluruh field terkunci.
* Penjual dapat menonaktifkan listing (status **Nonaktif**).
* Penjual tidak dapat menghapus atau menonaktifkan listing yang memiliki job aktif atau transaksi.

### 13.4 Ajukan Inspeksi dan Pembayaran Simulasi (P0)

* Tombol **Ajukan Inspeksi** hanya muncul pada listing Aktif tanpa job aktif.
* Pemohon (pembeli atau penjual) melihat ringkasan: kendaraan, tarif, dan opsi waktu/tempat yang ditetapkan penjual.
* **Halaman pembayaran simulasi** menyerupai gateway asli: pilih metode (QRIS, transfer bank, e-wallet), lalu hasil dapat dipilih **Berhasil**, **Gagal**, atau **Tertunda** untuk keperluan demo.
* Hasil pembayaran dicatat di ledger. Pembayaran berhasil membuat job berstatus **Dibayar**.
* Setiap permintaan pembayaran memakai *idempotency key* agar klik ganda tidak menghasilkan dua tagihan.
* Tagihan yang tidak dibayar dalam 30 menit kedaluwarsa dan listing dapat diajukan inspeksi lagi [ASUMSI].

### 13.5 Daftar Job dan Ambil Job (Inspektor, P0)

* Daftar job menampilkan jenis kendaraan, merek/model, area (kecamatan), tarif bersih untuk inspektor, opsi waktu penjual, dan usia job.
* Filter: jenis kendaraan, area, tarif. Urutan: terbaru, tarif tertinggi.
* **Ambil Job** bersifat atomik. Jika dua inspektor menekan hampir bersamaan, hanya satu yang berhasil. Yang lain melihat pesan "Job sudah diambil".
* Inspektor tidak dapat mengambil job atas listing miliknya sendiri (8.3).
* Inspektor dapat membatalkan job sebelum **Mulai Inspeksi**. Job kembali ke daftar.
* Satu inspektor dibatasi jumlah job aktif bersamaan [ASUMSI: 3].

### 13.6 Form Inspeksi dan Hasil (P0)

* Form dibuat **dari template** per jenis kendaraan (Bagian 15), sehingga indikator mobil dan motor dapat berbeda tanpa mengubah kode.
* Setiap item memiliki penilaian (Baik, Cukup, Buruk), catatan, dan foto jika item mewajibkan.
* **Simpan draf** otomatis di server tiap kali berpindah kategori, agar pekerjaan tidak hilang jika tab tertutup.
* Tombol **Kirim Hasil** aktif hanya jika semua item wajib terisi dan jumlah foto minimal terpenuhi.
* Setelah dikirim, laporan terkunci.
* Sistem menghitung skor total dan rekomendasi akhir (Bagian 15.3). Inspektor menambahkan catatan ringkasan.

### 13.7 Tampilan Hasil Inspeksi (P0)

* Ringkasan: skor, rekomendasi, tanggal, nama inspektor, rating inspektor.
* Rincian per kategori dengan penilaian, catatan, dan foto bertimestamp.
* Jika belum berhak, halaman menampilkan ringkasan terbatas dan tombol **Buka Hasil** beserta biaya akses.
* Tidak ada tombol unduh atau cetak.

### 13.8 Dashboard (P0)

| Role | Konten |
| --- | --- |
| Pembeli | Inspeksi berjalan, transaksi terakhir, hasil yang bisa diakses |
| Penjual | Listing aktif dan statusnya, status inspeksi per listing, pengajuan beli (P1) |
| Inspektor | Job tersedia, job saya, total pendapatan (bersih), rating, jumlah inspeksi |
| Admin | Jumlah pengguna, job per status, total pendapatan platform, antrean validasi listing dan inspektor |

Grafik bersifat sederhana (garis atau batang) dan tidak wajib pada P0. Cukup kartu angka.

### 13.9 Pendapatan Inspektor dan Ledger (P0)

* Setiap pembayaran dicatat sebagai entri ledger dengan jenis: `inspection_fee`, `access_fee`, `sale_commission`, `platform_fee`, `refund`.
* Saldo inspektor adalah jumlah entri kredit bersihnya. Dalam simulasi tidak ada penarikan nyata. Tombol **Tarik Saldo** (simulasi) boleh ditambahkan untuk demo (P2).
* Halaman Pendapatan menampilkan rincian per sumber.

### 13.10 Pengaturan Akun (P0)

* Ubah nama dan nomor HP, ganti password, dan (inspektor) perbarui dokumen.
* Perubahan email memerlukan verifikasi [OPEN].

### 13.11 Pengaturan Admin (P0)

* Tarif inspeksi per jenis kendaraan (motor Rp15.000, mobil Rp50.000; tarif yang sama berlaku sebagai biaya akses hasil), persentase komisi platform (default 10%), dan persentase komisi transaksi inspektor (default 3%).
* Perubahan nilai hanya berlaku untuk job dan transaksi baru.
* Pengelolaan template inspeksi (tambah, ubah, urutkan item, tandai wajib dan wajib foto). Jika pengelolaan via UI tidak sempat, template cukup di-*seed* lewat database pada P0 dan diberi UI pada P1.

### 13.12 Transaksi Jual Beli (P1)

| Field | Tipe | Wajib | Aturan |
| --- | --- | :---: | --- |
| Listing | Otomatis | Ya | Hanya listing Aktif atau Dalam Inspeksi milik orang lain |
| Harga tawaran | Angka (Rp) | Ya | > 0, default harga listing |
| Catatan | Teks | Tidak | Maksimal 300 karakter |

Aturan:

* Satu listing hanya boleh memiliki satu transaksi berjalan.
* Pembelian boleh diajukan/disetujui saat listing **Dalam Inspeksi**, tetapi pembayaran menunggu tidak adanya job aktif (D-02).
* Penjual melihat harga, komisi inspektor bila ada hasil, dan jumlah diterima sebelum Setuju/Tolak.
* Pembayaran memakai modul simulasi dan idempotency key yang sama dengan 13.4.
* Dana **Ditahan** sampai pembeli Konfirmasi Serah Terima atau admin menyelesaikan.
* Pembeli tidak dapat membeli listing sendiri.
### 13.13 Chat (P1)

* Pesan teks, maksimal 1.000 karakter per pesan.
* Percakapan unik per pasangan peserta dan konteks (listing atau job).
* Pesan baru, penanda belum dibaca, dan status koneksi diperbarui real-time lewat Laravel Echo + Pusher (cadangan: polling Livewire 3–5 detik). Target tampil di lawan bicara kurang dari 2 detik dengan Pusher.
* Koneksi terputus: tampil indikator "menyambung ulang", sambung ulang otomatis, lalu pesan yang terlewat diambil. Pesan yang gagal terkirim ditandai dan dapat dikirim ulang.
* Pengguna hanya menerima pesan dari percakapan yang diikutinya (Policy Laravel dan kanal Echo privat).
* Tidak ada pengiriman berkas atau gambar pada versi lomba.

### 13.14 Rating Inspektor (P1)

| Field | Tipe | Wajib | Aturan |
| --- | --- | :---: | --- |
| Skor | Bintang | Ya | 1–5 |
| Komentar | Teks | Tidak | Maksimal 500 karakter |

Aturan:

* Hanya pembeli dari transaksi berstatus **Selesai** yang dapat memberi rating, satu kali per transaksi.
* Rating rata-rata dan jumlah rating inspektor diperbarui otomatis dan tampil pada hasil inspeksi dan dashboard.
* Rating tidak dapat diubah setelah dikirim. Admin dapat menyembunyikan rating yang melanggar ketentuan.

### 13.15 Notifikasi dalam Web (P1)

* Notifikasi tampil di ikon lonceng dengan penanda belum dibaca, diperbarui real-time.
* Kejadian pemicu [ASUMSI]:

| Penerima | Kejadian |
| --- | --- |
| Inspektor | Job baru yang sesuai keahliannya, verifikasi disetujui atau ditolak |
| Penjual | Listing disetujui atau ditolak, job diambil, jadwal ditetapkan, hasil dikirim, pengajuan beli |
| Pemohon inspeksi | Job diambil, jadwal ditetapkan, hasil dikirim |
| Pembeli | Pengajuan beli disetujui atau ditolak |
| Semua | Pesan chat baru, pembayaran berhasil |

* Tidak ada notifikasi email, WhatsApp, atau push di luar web.

---

## 14. Status dan State Machine

### 14.1 Status Listing

| Kode | Label | Keterangan |
| --- | --- | --- |
| `pending_review` | Menunggu Validasi | Baru dibuat atau diedit |
| `rejected` | Ditolak | Ditolak admin, dapat diperbaiki |
| `active` | Aktif | Tampil publik |
| `in_inspection` | Dalam Inspeksi | Ada job aktif |
| `in_transaction` | Dalam Transaksi | Pembeli sudah membayar, menunggu serah terima (P1) |
| `sold` | Terjual | Transaksi selesai, akses hasil ditutup |
| `inactive` | Nonaktif | Dinonaktifkan penjual atau admin |

Transisi utama: `pending_review → active | rejected`, `rejected → pending_review`, `active → in_inspection → active`, `active → in_transaction → sold`, `active ↔ inactive`.

### 14.2 Status Job Inspeksi

| Kode | Label | Pemicu |
| --- | --- | --- |
| `requested` | Diajukan | Ajukan Inspeksi (belum bayar) |
| `paid` | Dibayar | Pembayaran berhasil; tampil di daftar |
| `taken` | Diambil | Inspektor mengambil |
| `scheduled` | Dijadwalkan | Jadwal final ditetapkan |
| `in_progress` | Sedang Diinspeksi | Mulai Inspeksi |
| `result_submitted` | Hasil Diinput | Inspektor mengirim hasil |
| `completed` | Selesai | Pemohon menerima, otomatis 3 hari (`auto_accept_days`), atau admin |
| `cancelled` | Dibatalkan | Pemohon/admin atau otomatis setelah `paid` 7 hari |
| `expired` | Kedaluwarsa | Tagihan tidak dibayar 30 menit [ASUMSI] |

Aturan transisi: `requested → paid | expired | cancelled`; `paid → taken | cancelled`, termasuk timeout 7 hari dari `available_since` (D-01); `taken → paid` bila inspektor batal; `scheduled → in_progress | paid | cancelled`; `in_progress → result_submitted`; `result_submitted → completed` saat pemohon menerima, otomatis 3 hari, atau admin (D-03). Status akhir: `completed`, `cancelled`, `expired`.

### 14.3 Status Transaksi Jual Beli (P1)

`offered` → `accepted` → `paid_held` → `completed`, dengan cabang `rejected`, `cancelled`, dan `expired`. `offered` 48 jam; `accepted` 24 jam sejak pembayaran dibuka; `paid_held` 7 hari tanpa serah terima perlu tindakan admin, tidak otomatis selesai.
---

## 15. Template Inspeksi

### 15.1 Prinsip

Indikator inspeksi akan **ditulis oleh tim** dan berbeda antara mobil dan motor. Karena itu inspeksi dirancang **berbasis template**: kategori dan item disimpan sebagai data, bukan ditulis permanen di kode. Menambah atau mengubah indikator tidak membutuhkan perubahan program.

Sampai tim menyerahkan indikator final, sistem memakai **template sementara** di bawah ini sebagai placeholder. Hasil inspeksi selalu mencatat versi template yang dipakai.

### 15.2 Template Sementara (Placeholder)

| Kategori | Contoh item | Foto wajib |
| --- | --- | :---: |
| Dokumen | STNK, BPKB, pajak, kecocokan nomor rangka dan mesin | Ya |
| Eksterior | Bodi, cat, kaca, lampu, ban | Ya |
| Interior (mobil) / Jok dan panel (motor) | Jok, dashboard, AC (mobil), speedometer | Ya |
| Mesin | Kondisi mesin, kebocoran oli, suara | Ya |
| Kaki-kaki | Suspensi, rem, rangka, roda | Ya |
| Kelistrikan | Aki, lampu, klakson, kelistrikan umum | Tidak |
| Test drive / test ride | Perpindahan gigi, kemudi, rem saat berjalan | Tidak |
| Riwayat tabrak / banjir | Indikasi bekas tabrak, perbaikan, atau banjir | Ya |

Setiap item dinilai **Baik / Cukup / Buruk**, dengan catatan opsional dan foto jika wajib. Jumlah minimal foto per inspeksi mengikuti indikator final tim [OPEN].

### 15.3 Penilaian dan Rekomendasi [ASUMSI]

* Skor item: Baik = 2, Cukup = 1, Buruk = 0. Setiap item dapat diberi **bobot**.
* **Skor total** = (jumlah skor berbobot ÷ skor maksimum) × 100.

| Skor total | Rekomendasi |
| --- | --- |
| ≥ 80 | Layak |
| 60 – 79 | Perlu Perhatian |
| < 60 | Tidak Direkomendasikan |

* **Item kritis** (misalnya nomor rangka/mesin tidak cocok, indikasi bekas banjir berat) yang dinilai Buruk otomatis menjadikan rekomendasi **Tidak Direkomendasikan**, berapa pun skornya.

---

## 16. Lokasi, Foto, dan Timestamp

### 16.1 Lokasi

* Penjual memilih **titik di peta** (Leaflet + OpenStreetMap) dan mengisi alamat lengkap.
* Titik harus berada di wilayah Pekanbaru. Di luar wilayah, sistem menolak dengan pesan jelas.
* **Privasi:** publik hanya melihat **area perkiraan** (kecamatan atau kelurahan) dan peta tanpa penanda persis. Koordinat dan alamat persis hanya dikirim ke pemilik, inspektor yang mengambil job, dan admin. Pembatasan dilakukan di server atau database, bukan hanya disembunyikan di tampilan.
* Inspektor melihat lokasi persis di peta setelah **Ambil Job**, termasuk tautan buka di aplikasi peta di HP.
* Tidak ada pelacakan lokasi terus-menerus. Lokasi hanya digunakan pada data listing.

### 16.2 Foto

**Foto listing:** unggah dari galeri atau kamera, maksimal 10 foto, 2 MB per foto. Foto dikompresi di sisi klien sebelum unggah.

**Foto inspeksi:** harus diambil **langsung dari kamera** dan dilengkapi **timestamp**.

* Pengambilan memakai komponen kamera di dalam halaman (akses kamera browser lewat HTTPS), dengan cadangan input kamera bawaan HP.
* Setiap foto diberi **cap waktu dan ID job** yang ditanam pada gambar, serta waktu unggah server yang disimpan di database.
* Foto dari galeri tidak diterima pada formulir inspeksi.
* Foto inspeksi disimpan di penyimpanan privat dan ditampilkan lewat tautan bertanda waktu terbatas.

> **Batasan web.** Browser tidak dapat menjamin 100% bahwa foto berasal dari kamera. Komponen kamera di dalam halaman, cap waktu server, dan pemeriksaan metadata mengurangi risiko, tetapi tidak menghilangkannya. Untuk produksi, tinjauan admin acak dapat ditambahkan.

### 16.3 Timestamp

Seluruh waktu disimpan dalam UTC dan ditampilkan dalam **WIB**. Waktu server menjadi acuan, bukan waktu perangkat.

---

## 17. Autentikasi dan Keamanan Akun

* Login dengan **email dan password** (Laravel Breeze). "Username" diwujudkan sebagai nama tampilan.
* Password minimal 8 karakter, disimpan sebagai hash oleh penyedia autentikasi.
* **Reset password** melalui tautan email.
* Verifikasi email: dimatikan pada P0 agar demo tidak terhambat, dan dinyalakan ketika pengiriman email produksi sudah disiapkan [ASUMSI] [OPEN]. Lihat risiko R3 di Bagian 25.
* Tingkat keamanan: **menengah**. Seluruh akses data dibatasi **Policy dan Gate** Laravel per role, pembatasan laju (*rate limit*) pada login dan API sensitif, dan validasi input di server.
* Akun admin hanya dibuat manual.
* Akun demo (Bagian 23.4) dinonaktifkan atau diganti passwordnya setelah lomba berakhir.
* Satu akun dapat dipakai di banyak perangkat. Fitur logout dari semua perangkat termasuk P2.

---

## 18. Data Model Awal

Nama tabel dan kolom memakai bahasa Inggris snake_case. `id` memakai UUID, dan setiap tabel memiliki `created_at` dan `updated_at` (tidak diulang di bawah).

### 18.1 users

Tabel `users` bawaan Laravel diperluas dengan kolom di bawah, sehingga tidak ada tabel `profiles` terpisah.

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | uuid | Primary key |
| email, password | string | Bawaan Laravel. Email unik |
| full_name | text | |
| phone | text | |
| role | enum | `user`, `inspector`, `admin` |
| status | enum | `active`, `blocked` |
| avatar_url | text | Opsional |
| accepted_terms_at | timestamp (UTC) | Waktu menyetujui syarat (P1) |

### 18.2 inspector_profiles

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| user_id | uuid | FK ke users |
| vehicle_types | json (array) | `car`, `motorcycle` |
| experience_years | int | |
| verification_status | enum | `pending`, `approved`, `rejected` |
| rejection_reason | text | |
| verified_by, verified_at | uuid, timestamp | Admin dan waktu verifikasi |
| rating_avg | numeric(3,2) | Dihitung dari ratings |
| rating_count | int | |
| completed_jobs | int | |

### 18.3 inspector_documents

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | uuid | |
| user_id | uuid | FK |
| type | enum | `ktp`, `certificate`, `cv`, `portfolio` |
| storage_path | text | Path di penyimpanan privat |

### 18.4 listings

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | uuid | |
| seller_id | uuid | FK users |
| vehicle_type | enum | `car`, `motorcycle` |
| title, brand, model | text | |
| year | int | |
| price | bigint | Rupiah |
| negotiable | bool | |
| odometer_km | int | |
| transmission, fuel, color | text | |
| chassis_number | text | Opsional. **Tidak dikirim ke publik** |
| description | text | |
| area_name | text | Kecamatan/kelurahan untuk tampilan publik |
| lat, lng | numeric | Koordinat persis, **tidak dikirim ke publik** |
| address_full | text | **Tidak dikirim ke publik** |
| inspection_place | text | Tempat inspeksi pilihan penjual |
| status | enum | Lihat 14.1 |
| rejection_reason | text | |
| is_inspected | bool | Dipakai untuk lencana |
| last_inspected_at | timestamp (UTC) | |
| validated_by, validated_at | uuid, timestamp | |

### 18.5 listing_photos

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id, listing_id | uuid | |
| storage_path | text | |
| sort_order | int | |
| is_cover | bool | |

### 18.6 listing_inspection_slots

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id, listing_id | uuid | |
| slot_start | timestamp (UTC) | Opsi waktu dari penjual |
| note | text | |

### 18.7 inspection_jobs

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id | uuid | |
| listing_id | uuid | FK |
| requester_id | uuid | Pemohon (pembeli atau penjual) |
| inspector_id | uuid | Null sampai diambil |
| status | enum | Lihat 14.2 |
| fee_amount | bigint | Tarif saat dibuat (disalin agar perubahan tarif tidak memengaruhi job lama) |
| platform_fee_pct | numeric | Persentase komisi saat dibuat (disalin) |
| expires_at | timestamp (UTC) | Batas pembayaran tagihan |
| available_since | timestamp (UTC) | Waktu mulai job tersedia; diatur ulang saat job kembali ke `paid` setelah inspektor membatalkan |
| scheduled_at | timestamp (UTC) | Jadwal final |
| taken_at, started_at, submitted_at, completed_at | timestamp (UTC) | |
| needs_attention_at | timestamp (UTC) | Ditandai bila jadwal belum ditetapkan (E8) |
| cancel_reason | text | |
| cancelled_by | uuid | |

Pembatas basis data: **hanya satu job aktif per listing**. MySQL tidak mendukung indeks unik parsial, sehingga dipakai kolom virtual `active_listing_id` (bernilai `listing_id` bila status bukan `completed`, `cancelled`, atau `expired`, selain itu `NULL`) dengan **indeks unik**. MySQL mengizinkan banyak nilai `NULL`, sehingga job berstatus akhir tidak bentrok. Enum disimpan sebagai string yang dibatasi validasi dan `CHECK`, dan kolom berisi array disimpan sebagai JSON.

### 18.8 inspection_templates dan template_items

* **inspection_templates:** `id`, `vehicle_type`, `name`, `version`, `is_active`.
* **template_items:** `id`, `template_id`, `category`, `label`, `weight`, `is_required`, `requires_photo`, `is_critical`, `sort_order`.

### 18.9 inspection_results

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id, job_id (unik), listing_id, inspector_id | uuid | |
| template_id | uuid | Versi template yang dipakai |
| total_score | numeric | 0–100 |
| recommendation | enum | `recommended`, `caution`, `not_recommended` |
| summary_note | text | |
| status | enum | `draft`, `submitted` |
| submitted_at | timestamp (UTC) | |

### 18.10 result_items dan result_photos

* **result_items:** `id`, `result_id`, `template_item_id`, `rating` (`good`, `fair`, `poor`), `note`.
* **result_photos:** `id`, `result_id`, `result_item_id`, `storage_path`, `captured_at` (waktu server), `stamped_at` (waktu yang tertanam), `is_camera_capture`.

### 18.11 result_access

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| id, result_id, user_id | uuid | |
| source | enum | `requester`, `paid_access` |
| payment_id | uuid | FK payments |
| granted_at | timestamp (UTC) | |

Unik pada (`result_id`, `user_id`).

### 18.12 payments dan ledger_entries

* **payments:** `id`, `payer_id`, `purpose` (`inspection`, `access`, `purchase`), `ref_id` (job, hasil, atau transaksi), `amount`, `status` (`pending`, `success`, `failed`, `refunded`), `method`, `idempotency_key` (unik), `paid_at`.
* **ledger_entries:** `id`, `payment_id`, `account_user_id` (null untuk platform), `entry_type` (`inspection_fee`, `access_fee`, `sale_commission`, `platform_fee`, `refund`), `amount` (positif kredit, negatif debit), `note`.

### 18.13 transactions (P1)

`id`, `listing_id`, `buyer_id`, `seller_id`, `offered_price`, `status` (Bagian 14.3), `payment_id`, `inspector_commission`, `platform_commission`, `completed_at`, `cancelled_by`, `cancel_reason`, `cancel_requested_at`, `needs_attention_at`.

### 18.14 conversations dan messages (P1)

* **conversations:** `id`, `context_type` (`listing`, `job`), `context_id`.
* **conversation_members:** `conversation_id`, `user_id`, `last_read_at`.
* **messages:** `id`, `conversation_id`, `sender_id`, `body`.

Pesan dan notifikasi baru disiarkan lewat Laravel Echo pada **kanal privat** (`private-conversation.{id}`, `private-user.{id}`). Otorisasi kanal memastikan hanya peserta percakapan atau pemilik notifikasi yang menerima data.

### 18.15 ratings (P1)

`id`, `transaction_id` (unik), `inspector_id`, `buyer_id`, `score` (1–5), `comment`, `is_hidden`.

### 18.16 notifications (P1)

`id`, `user_id`, `type`, `title`, `body`, `link`, `read_at`.

### 18.17 app_settings

`key`, `value` (json). Berisi: `inspection_fee_car` (50000), `inspection_fee_motorcycle` (15000), `platform_fee_pct` (10), `sale_commission_pct` (3), `auto_accept_days` (3), `paid_job_timeout_days` (7).

### 18.18 audit_logs (P2)

`id`, `actor_id`, `action`, `entity`, `entity_id`, `meta` (json).

---

## 19. API Contract Awal

Endpoint internal Laravel di bawah `/api/v1`. Halaman utama dirender server (Blade + Livewire) dan memakai service/action yang sama dengan endpoint ini, sehingga aturan bisnis hanya ada di satu tempat. **Operasi yang mengubah uang, status job, atau akses data sensitif selalu dijalankan di server** dan dilindungi Policy, agar aturan bisnis tidak dapat dilewati dari browser. Login dan logout ditangani Laravel Breeze (sesi dan CSRF).

Format respons: `{ "data": ..., "error": null }` atau `{ "data": null, "error": { "code": "...", "message": "..." } }`.

### 19.1 Ringkasan Endpoint

| Method | Path | Role | Fungsi |
| --- | --- | --- | --- |
| POST | `/auth/register` | Publik | Daftar pengguna atau inspektor |
| POST | `/inspector/documents` | Inspektor | Unggah atau ganti dokumen verifikasi |
| GET | `/listings` | Publik | Daftar listing dengan filter dan pagination |
| GET | `/listings/{id}` | Publik | Detail listing (tanpa data persis) |
| POST | `/listings` | User | Buat listing |
| PATCH | `/listings/{id}` | Pemilik | Edit listing |
| POST | `/listings/{id}/photos` | Pemilik | Unggah foto |
| POST | `/inspections` | User | Ajukan inspeksi, membuat tagihan |
| POST | `/payments/simulate` | Pemilik tagihan | Menyelesaikan pembayaran simulasi |
| GET | `/templates?vehicle_type=` | Inspektor | Template inspeksi aktif |
| GET | `/jobs` | Inspektor | Daftar job terbuka |
| GET | `/jobs/mine` | Inspektor, pemohon | Job milik sendiri |
| GET | `/jobs/{id}` | Peserta job, admin | Detail job (lokasi persis hanya untuk inspektor pengambil) |
| POST | `/jobs/{id}/take` | Inspektor | Ambil job (atomik) |
| POST | `/jobs/{id}/cancel` | Pemohon, inspektor, admin | Batalkan job |
| POST | `/jobs/{id}/schedule` | Inspektor | Tetapkan jadwal |
| POST | `/jobs/{id}/start` | Inspektor | Mulai inspeksi |
| PUT | `/jobs/{id}/result` | Inspektor | Simpan draf hasil |
| POST | `/jobs/{id}/result/photos` | Inspektor | Unggah foto inspeksi |
| POST | `/jobs/{id}/result/submit` | Inspektor | Kirim hasil |
| POST | `/jobs/{id}/accept` | Pemohon | Terima hasil, job selesai |
| GET | `/results/{id}` | Berhak | Hasil inspeksi (terkunci atau terbuka) |
| POST | `/results/{id}/access` | User | Bayar akses hasil |
| GET | `/me/earnings` | Inspektor | Saldo dan rincian pendapatan |
| POST | `/transactions` | User | Ajukan pembelian (P1) |
| PATCH | `/transactions/{id}` | Penjual, pembeli | Setuju, tolak, bayar, serah terima (P1) |
| GET/POST | `/conversations`, `/messages` | Peserta | Chat real-time (P1). Pesan baru diterima lewat Laravel Echo (Pusher), cadangan polling |
| POST | `/ratings` | Pembeli | Beri rating (P1) |
| GET | `/notifications` | Semua | Daftar notifikasi (P1) |
| PATCH | `/notifications/{id}/read` | Pemilik | Tandai dibaca (P1) |
| GET | `/dashboard` | Semua | Ringkasan sesuai role |
| GET/PATCH | `/admin/listings`, `/admin/inspectors` | Admin | Validasi dan verifikasi |
| POST | `/admin/jobs/{id}/complete`, `/admin/jobs/{id}/cancel` | Admin | Selesaikan atau batalkan job manual |
| PATCH | `/admin/users/{id}` | Admin | Blokir atau aktifkan pengguna (P1) |
| GET/PUT | `/admin/settings` | Admin | Tarif dan komisi |

### 19.2 Registrasi

`POST /auth/register`

```json
{
  "account_type": "inspector",
  "full_name": "Budi Santoso",
  "email": "budi@example.com",
  "phone": "081234567890",
  "password": "********",
  "vehicle_types": ["car", "motorcycle"],
  "experience_years": 8
}
```

Respons `201`:

```json
{
  "data": {
    "id": "uuid",
    "role": "inspector",
    "verification_status": "pending"
  },
  "error": null
}
```

Dokumen verifikasi dikirim terpisah lewat `POST /inspector/documents` (`multipart/form-data`: `type`, `file`) setelah akun dibuat.

### 19.3 Daftar Listing

`GET /listings?type=car&brand=Toyota&price_min=50000000&price_max=150000000&year_min=2015&area=Tampan&inspected=true&sort=newest&page=1&page_size=12`

```json
{
  "data": {
    "items": [
      {
        "id": "uuid",
        "title": "Toyota Avanza 2018 Terawat",
        "price": 135000000,
        "year": 2018,
        "odometer_km": 62000,
        "area_name": "Tampan",
        "cover_url": "https://.../cover.webp",
        "is_inspected": true,
        "status": "active"
      }
    ],
    "page": 1,
    "page_size": 12,
    "total": 48
  },
  "error": null
}
```

### 19.4 Ajukan Inspeksi

`POST /inspections`

```json
{
  "listing_id": "uuid"
}
```

Respons `201`:

```json
{
  "data": {
    "job_id": "uuid",
    "status": "requested",
    "fee_amount": 50000,
    "payment_id": "uuid",
    "expires_at": "2026-10-06T10:30:00+07:00"
  },
  "error": null
}
```

Kesalahan: `409 LISTING_HAS_ACTIVE_JOB`, `403 NOT_ALLOWED`, `422 LISTING_NOT_ACTIVE`.

### 19.5 Pembayaran Simulasi

`POST /payments/simulate`

```json
{
  "payment_id": "uuid",
  "method": "qris",
  "outcome": "success",
  "idempotency_key": "uuid-client"
}
```

`outcome`: `success`, `failed`, atau `pending`. Hanya pemilik tagihan yang dapat memanggilnya. Mode demo ini dimatikan di produksi bila kelak memakai gateway nyata.

Respons `200`:

```json
{
  "data": { "payment_id": "uuid", "status": "success", "job_status": "paid" },
  "error": null
}
```

Kesalahan: `410 PAYMENT_EXPIRED` bila tagihan sudah kedaluwarsa.

### 19.6 Ambil Job

`POST /jobs/{id}/take`

Respons `200`:

```json
{
  "data": {
    "job_id": "uuid",
    "status": "taken",
    "location": {
      "lat": 0.5071,
      "lng": 101.4478,
      "address_full": "Jl. Contoh No. 10, Tampan"
    },
    "chassis_number": "MHKA1234567890123"
  },
  "error": null
}
```

Kesalahan: `409 JOB_ALREADY_TAKEN`, `403 INSPECTOR_NOT_VERIFIED`, `403 OWN_LISTING`, `409 MAX_ACTIVE_JOBS`.

### 19.7 Simpan Draf dan Kirim Hasil

`PUT /jobs/{id}/result`

```json
{
  "template_id": "uuid",
  "items": [
    { "template_item_id": "uuid", "rating": "good", "note": "Tidak ada kebocoran" },
    { "template_item_id": "uuid", "rating": "poor", "note": "Rem depan aus" }
  ],
  "summary_note": "Kondisi umum baik, rem depan perlu diganti."
}
```

`POST /jobs/{id}/result/submit`. Respons `200`:

```json
{
  "data": {
    "job_status": "result_submitted",
    "total_score": 82.5,
    "recommendation": "recommended"
  },
  "error": null
}
```

Kesalahan: `422 REQUIRED_ITEMS_MISSING` (dengan daftar item), `422 MIN_PHOTOS_NOT_MET`.

### 19.8 Foto Inspeksi

`POST /jobs/{id}/result/photos` (`multipart/form-data`): `file`, `template_item_id`, `stamped_at`.

Respons `201`:

```json
{
  "data": { "photo_id": "uuid", "captured_at": "2026-10-09T09:12:44+07:00" },
  "error": null
}
```

Kesalahan: `413 FILE_TOO_LARGE`, `415 UNSUPPORTED_TYPE`, `422 NOT_CAMERA_CAPTURE`.

### 19.9 Hasil dan Akses

`GET /results/{id}`. Jika belum berhak, respons `200` hanya memuat ringkasan terbatas:

```json
{
  "data": {
    "locked": true,
    "inspected_at": "2026-10-09",
    "access_fee": 50000,
    "inspector": { "name": "Budi S.", "rating_avg": 4.6 }
  },
  "error": null
}
```

`POST /results/{id}/access` membuat tagihan akses, lalu diselesaikan lewat `/payments/simulate`. Jika sukses, `result_access` dibuat dan ledger mencatat pembagian pendapatan. Jika pengguna sudah memiliki akses, tidak ada tagihan baru.

### 19.10 Pengaturan Admin

`PUT /admin/settings`

```json
{
  "inspection_fee_car": 50000,
  "inspection_fee_motorcycle": 15000,
  "platform_fee_pct": 10,
  "sale_commission_pct": 3
}
```

### 19.11 Kode Kesalahan Umum

| HTTP | Kode | Arti |
| --- | --- | --- |
| 400 | `VALIDATION_ERROR` | Input tidak valid, disertai daftar field |
| 401 | `UNAUTHENTICATED` | Belum login atau sesi berakhir |
| 403 | `NOT_ALLOWED` | Tidak punya hak |
| 404 | `NOT_FOUND` | Data tidak ditemukan |
| 409 | `CONFLICT` | Konflik status (job sudah diambil, dsb.) |
| 410 | `PAYMENT_EXPIRED` | Tagihan sudah kedaluwarsa |
| 422 | `BUSINESS_RULE` | Melanggar aturan bisnis |
| 429 | `RATE_LIMITED` | Terlalu banyak permintaan |
| 500 | `INTERNAL_ERROR` | Kesalahan server |

---

## 20. Kebutuhan Non-Fungsional

### 20.1 Performa

Target berikut adalah **[ASUMSI]** karena tim belum menetapkan angka.

| Aspek | Target |
| --- | --- |
| Waktu muat halaman daftar barang (jaringan 4G) | LCP < 2,5 detik |
| Respons API baca (p95) | < 500 ms |
| Respons API tulis (p95) | < 1 detik |
| Skor Lighthouse mobile | Performance ≥ 80, Accessibility ≥ 90 |
| Ukuran foto yang ditampilkan | Versi thumbnail di daftar, versi penuh hanya di detail |

Strategi: pagination, *lazy load* gambar, kompresi foto di klien, format WebP bila memungkinkan, dan *caching* pada halaman publik.

### 20.2 Responsivitas dan Kompatibilitas

* Desain **mobile-first**, dengan titik henti sekitar 640, 768, dan 1024 piksel.
* Area sentuh minimal 44 piksel.
* Formulir inspeksi dioptimalkan untuk layar HP satu tangan.

### 20.3 Keandalan dan Backup

* Tim menjawab kebutuhan backup dengan "7 hari". Diartikan sebagai **penyimpanan cadangan database selama 7 hari** [OPEN].
* Ketersediaan cadangan otomatis bergantung pada paket penyedia database. Jika paket yang dipakai tidak menyediakannya, cadangan dibuat lewat ekspor terjadwal ke penyimpanan terpisah.
* Uji pemulihan dilakukan sekali sebelum lomba.

### 20.4 Keamanan

* **Policy** Laravel untuk setiap model dan aksi (`authorize` pada setiap controller dan komponen Livewire). Tidak ada rute data privat tanpa middleware autentikasi dan policy eksplisit. Query data milik pengguna selalu dibatasi lewat relasi atau *global scope*.
* Kredensial (`.env`, kunci Pusher, `APP_KEY`) tidak pernah dikirim ke browser atau dimasukkan ke repositori.
* Validasi input di server (tipe, panjang, rentang), bukan hanya di formulir.
* Perlindungan terhadap XSS (teks pengguna di-*escape*), CSRF, dan unggahan berbahaya (periksa tipe MIME dan ukuran).
* Pembatasan laju pada login, registrasi, reset password, dan pembayaran simulasi.
* Seluruh lalu lintas melalui HTTPS.
* Dokumen inspektor dan foto inspeksi di penyimpanan privat dengan tautan sementara.
* Lokasi persis dan nomor rangka tidak pernah muncul di respons API publik.
* Operasi uang bersifat **idempoten** dan dibungkus transaksi database.

### 20.5 Privasi dan Kepatuhan

* Data yang dikumpulkan termasuk **data pribadi** menurut UU No. 27 Tahun 2022 tentang Pelindungan Data Pribadi: nama, email, nomor HP, alamat, KTP inspektor, dan dokumen kendaraan.
* Karena itu PRD ini mewajibkan:
  * halaman **Kebijakan Privasi** dan **Syarat Ketentuan** (P1), berisi tujuan pengumpulan, pihak yang dapat melihat, masa simpan, dan kontak;
  * persetujuan eksplisit saat registrasi;
  * akses dokumen inspektor hanya untuk admin dan pemilik.
* Masa simpan data dan aturan penghapusan akun: [OPEN]. Usulan sementara: data akun dihapus atas permintaan, dan data transaksi disimpan sesuai kebutuhan pencatatan.
* Disclaimer inspeksi (P1): hasil inspeksi adalah penilaian pada saat inspeksi dilakukan dan tidak menjadi jaminan mutlak. Tanggung jawab inspektor dan platform diatur di Syarat Ketentuan. Rating menjadi sarana umpan balik utama [OPEN: kebijakan tanggung jawab detail].

### 20.6 Usability

* Bahasa Indonesia yang sederhana, tanpa istilah teknis berlebihan pada layar pembeli.
* Status job dan listing selalu terlihat jelas dengan label dan warna yang tidak bergantung pada warna saja.
* Pesan kesalahan menyebut penyebab dan langkah perbaikan.
* Tindakan yang tidak dapat dibatalkan (kirim hasil, bayar, serah terima) selalu meminta konfirmasi.
* Keadaan kosong (*empty state*), memuat, dan gagal dirancang untuk setiap halaman.

### 20.7 Observabilitas

* Pencatatan galat sisi server dan klien.
* Tim meminta analitik pemakaian dan audit log. Pada P0, pencatatan galat cukup. Audit log aksi penting (status job, pembayaran, perubahan role, validasi) dan analitik dasar masuk P2.

### 20.8 Aksesibilitas

Kontras warna memenuhi WCAG AA, semua input memiliki label, navigasi dapat dijangkau keyboard, dan gambar bermakna memiliki teks alternatif.

---

## 21. Arah Desain dan Branding

Tim terbuka terhadap saran dan belum memiliki logo, palet, atau desain acuan.

### 21.1 Gaya

**Modern minimalis, bersih, dan terpercaya.** Banyak ruang kosong, tipografi jelas, sudut membulat sedang, dan ilustrasi seperlunya. Foto kendaraan menjadi pusat perhatian.

### 21.2 Palet Warna Usulan

| Token | Warna | Penggunaan |
| --- | --- | --- |
| `--primary` | `#12355B` (biru tua) | Tombol utama, header, tautan |
| `--accent` | `#F59E0B` (kuning amber) | Penekanan, lencana, sorotan |
| `--success` | `#16A34A` | Layak, berhasil |
| `--warning` | `#D97706` | Perlu Perhatian, menunggu |
| `--danger` | `#DC2626` | Tidak Direkomendasikan, galat |
| `--bg` | `#F8FAFC` | Latar halaman |
| `--surface` | `#FFFFFF` | Kartu |
| `--text` | `#0F172A` | Teks utama |
| `--muted` | `#64748B` | Teks pendukung |

Seluruh warna didefinisikan sebagai **token CSS** agar mode gelap (P2) hanya perlu menambahkan set token kedua.

### 21.3 Tipografi

Satu keluarga huruf sans-serif modern (misalnya Inter atau Plus Jakarta Sans) dengan cadangan sistem. Ukuran dasar teks 16 piksel.

### 21.4 Nama dan Identitas

* Kepanjangan SIMOKAS belum ditetapkan. Usulan: **S**istem **I**nspeksi **Mo**bil dan Motor **K**endaraan Bekas **A**man **S**ahih. Tim dapat memilih kepanjangan lain yang lebih disukai [OPEN].
* Usulan tagline: *"Beli bekas, tenang karena sudah diinspeksi."*
* Logo: lencana perisai dengan tanda centang dan siluet kendaraan, sederhana dan terbaca pada ukuran kecil.

### 21.5 Komponen Kunci

Kartu listing, lencana "Sudah Diinspeksi", penanda status (*chip*), stepper status job, formulir checklist dengan pilihan tiga tingkat yang besar, komponen kamera, tampilan hasil dengan skor melingkar, dan halaman pembayaran simulasi.

### 21.6 Halaman Tambahan

Splash/onboarding tidak diperlukan. Halaman **Cara Kerja** dan **Bantuan/FAQ** singkat disarankan pada beranda untuk membantu pengguna awam dan juri. Halaman **Tentang** opsional.

---

## 22. Edge Cases dan Error States

| # | Situasi | Perilaku yang diharapkan |
| --- | --- | --- |
| E1 | Dua inspektor menekan Ambil Job bersamaan | Hanya satu berhasil (pembaruan atomik berkondisi). Yang lain melihat "Job sudah diambil" dan daftar diperbarui |
| E2 | Dua pemohon mengajukan inspeksi pada listing yang sama bersamaan | Pembatas basis data hanya mengizinkan satu job aktif. Yang kedua menerima `409` |
| E3 | Pengguna menekan Bayar dua kali atau menyegarkan halaman | *Idempotency key* mencegah tagihan ganda |
| E4 | Pembayaran simulasi gagal atau tertunda | Job tetap `requested`. Pengguna dapat mencoba lagi. Job kedaluwarsa setelah 30 menit tanpa bayar [ASUMSI] |
| E5 | Pengguna membuka akses hasil dua kali | Tidak ada tagihan kedua. Akses yang ada dipakai |
| E6 | Penjual menonaktifkan atau menghapus listing saat ada job aktif | Ditolak. Penjual harus membatalkan job terlebih dahulu |
| E7 | Inspektor membatalkan setelah mengambil job | Job kembali `paid`, tercatat untuk tinjauan admin |
| E8 | Penjual tidak responsif, jadwal tidak kunjung ditetapkan | Job otomatis ditandai "perlu tindakan" setelah 48 jam. Admin dapat membatalkan dan mengembalikan dana [ASUMSI] |
| E9 | Pin lokasi di luar Pekanbaru | Ditolak dengan pesan jelas |
| E10 | Foto lebih dari 2 MB atau bukan gambar | Dikompresi otomatis. Jika tetap gagal, pesan galat jelas |
| E11 | Izin kamera ditolak pada formulir inspeksi | Ditampilkan petunjuk mengaktifkan izin. Pengiriman hasil tidak dapat dilanjutkan tanpa foto yang diwajibkan |
| E12 | Koneksi putus saat mengisi formulir inspeksi | Draf tersimpan di server pada setiap perpindahan kategori. Pesan "koneksi terputus, mencoba lagi" ditampilkan |
| E13 | Sesi berakhir saat mengisi | Pengguna diminta masuk kembali dan draf dipulihkan |
| E14 | Pembeli melihat hasil, lalu kendaraan terjual | Akses ditutup untuk pembeli lain. Riwayat milik pembayar tetap tercatat |
| E15 | Penjual mengubah field inti listing setelah diinspeksi | Lencana dicabut atau memerlukan persetujuan admin [OPEN] |
| E16 | Inspektor ditolak verifikasi | Menerima alasan dan dapat mengajukan ulang dokumen |
| E17 | Pengguna diblokir saat memiliki job atau transaksi aktif | Admin menyelesaikan atau membatalkan item terkait sebelum atau bersamaan dengan pemblokiran |
| E18 | Pembeli dan penjual adalah akun yang sama | Pengajuan beli ditolak untuk listing milik sendiri |
| E19 | Email sudah terdaftar | Pesan "email sudah terdaftar" dengan tautan masuk atau reset |
| E20 | Tarif diubah admin saat ada job berjalan | Job memakai tarif yang disalin saat dibuat |
| E21 | Koneksi real-time chat terputus | Indikator "menyambung ulang", sambung ulang otomatis, lalu pesan yang terlewat diambil. Pesan yang gagal terkirim ditandai dan dapat dikirim ulang |
| E22 | Pesan dikirim saat lawan bicara sedang tidak membuka aplikasi | Pesan tersimpan dan tampil dengan penanda belum dibaca saat lawan bicara kembali |
| E23 | Kendaraan terjual tanpa pernah diinspeksi | Tidak ada komisi inspektor. Komisi hanya untuk transaksi pada listing yang punya hasil inspeksi |
| E24 | Inspektor mencoba mengambil job atas listing miliknya | Ditolak dengan `403 OWN_LISTING` dan job tidak tampil di daftarnya [ASUMSI] |
| E25 | Pembayaran dibuka setelah tagihan kedaluwarsa | Ditolak dengan `410 PAYMENT_EXPIRED`. Pemohon diminta mengajukan ulang |

### Edge case tambahan dari flow

| ID | Situasi | Perilaku |
| --- | --- | --- |
| JOB-E17 | Pemohon membatalkan saat `in_progress` | Ditolak; hanya admin |
| TRX-E10 | Serah terima tidak dikonfirmasi 7 hari | Ditandai perlu tindakan; admin memutuskan |
| TRX-E13 | Serah terima dikonfirmasi dua kali | Efek sekali |
| LST-E6 | Harga listing berubah setelah penawaran | Penawaran memakai harga tersalin |

---

## 23. Rencana Rilis, Pengujian, dan Timeline Pengerjaan

### 23.1 Tahapan

1. **Prototype dan uji internal** (sampai 8 Oktober): P0 selesai dan di-*deploy*.
2. **Uji coba dan perbaikan** (9–12 Oktober): P1 dikerjakan dan pengujian alur oleh orang di luar tim.
3. **Rilis penuh untuk lomba** (12 Oktober): pengumpulan web. Setelah itu hanya perbaikan galat kritis.
4. **Presentasi finalis** (16 Oktober): demo langsung.

### 23.2 Rencana Harian [ASUMSI]

Per 5 Oktober, waktu untuk P0 tersisa empat hari kerja (5–8 Oktober).

| Tanggal | Fokus | Hasil |
| --- | --- | --- |
| 5 Okt | Keputusan hosting, repo, skema database dan Policy, autentikasi, data seed. Listing: CRUD, unggah foto, pin lokasi | Fondasi dan sisi penjual siap |
| 6 Okt | Validasi listing oleh admin, pendaftaran dan verifikasi inspektor, ajukan inspeksi, pembayaran simulasi, daftar dan ambil job | Alur inspeksi berjalan sampai job diambil |
| 7 Okt | Form inspeksi berbasis template dengan foto kamera, tampilan hasil, akses berbayar, ledger, dashboard per role, pengaturan admin | **Alur P0 lengkap** |
| 8 Okt | Deploy, uji asap, perbaikan galat, data demo, uji lintas perangkat | **P0 live** |
| 9–10 Okt | Transaksi dalam platform (simulasi), chat real-time | P1 bagian 1 |
| 11 Okt | Rating, notifikasi, kebijakan privasi dan syarat ketentuan, riwayat | P1 bagian 2 |
| 12 Okt | Pemolesan UI, performa, uji akhir, **pengumpulan** | Rilis lomba |
| 13–16 Okt | Pembekuan fitur. Skrip demo dan presentasi. Hanya perbaikan kritis | Siap tampil |

> **Peringatan.** Empat hari untuk P0 dengan tiga orang sangat ketat. Peluangnya baru realistis jika form inspeksi berbasis template, pembayaran dibuat sebagai simulasi sederhana, dan fitur P1 benar-benar ditunda. Indikator inspeksi dari tim dibutuhkan paling lambat 6 Oktober. Jika terlambat, template sementara pada Bagian 15.2 tetap dipakai.

### 23.3 Strategi Pengujian

| Jenis | Cakupan |
| --- | --- |
| Uji alur utama (manual) | Skenario penuh pembeli, penjual, inspektor, dan admin dengan akun demo |
| Uji hak akses | Setiap role mencoba mengakses data role lain (harus ditolak), termasuk lokasi persis, nomor rangka, dan dokumen inspektor |
| Uji aturan bisnis | Perhitungan biaya, komisi platform 10%, komisi transaksi 3%, akses hasil, pembatalan, pengembalian, dan kedaluwarsa tagihan |
| Uji konkurensi | Dua pengguna mengambil job yang sama, klik ganda pembayaran |
| Uji perangkat | Android Chrome, iPhone Safari, laptop Chrome/Edge/Firefox |
| Uji performa | Lighthouse pada halaman daftar barang dan detail listing |
| Uji penerimaan pengguna | Dua sampai lima orang di luar tim menjalankan alur tanpa dibantu |

### 23.4 Data Demo

* 3 akun pembeli/penjual, 3 inspektor (2 terverifikasi, 1 menunggu), 1 admin.
* 15–20 listing mobil dan motor dengan foto, area Pekanbaru, dan status beragam.
* 4–5 hasil inspeksi lengkap dengan skor berbeda (Layak, Perlu Perhatian, Tidak Direkomendasikan).
* 2 job terbuka dan 1 job berjalan untuk mendemokan alur secara langsung.
* Akun demo untuk setiap role dengan kata sandi yang mudah dibagikan ke juri. Akun ini hanya untuk demo dan dinonaktifkan setelah lomba (Bagian 17).

### 23.5 Pelatihan dan Panduan

Halaman **Cara Kerja** di beranda dan teks bantuan singkat pada formulir. Panduan khusus inspektor (cara mengisi checklist dan mengambil foto) disarankan dalam bentuk satu halaman bergambar (P1).

---

## 24. Asumsi dan Dependensi

### 24.1 Asumsi

1. Paket Hostinger Cloud Startup dapat menjalankan Laravel 13 (PHP 8.3), cron per menit, dan koneksi keluar ke Pusher [OPEN: verifikasi di hPanel].
2. Tim sudah terdaftar pada lomba. Pendaftaran sudah ditutup pada 4 Oktober, sehingga status pendaftaran perlu dikonfirmasi.
3. Seluruh pembayaran adalah simulasi dan tidak ada uang nyata berpindah.
4. Tarif dan komisi pada Bagian 8 sudah ditetapkan tim, kecuali yang ditandai [ASUMSI], dan semuanya dapat diubah admin.
5. Jam kerja efektif tim cukup untuk P0 dalam 4 hari.
6. Inspektor memiliki HP dengan kamera dan koneksi internet saat menginput hasil.
7. Verifikasi inspektor dilakukan manual oleh admin berdasarkan dokumen yang diunggah, tanpa pengecekan otomatis.
8. Peta dan pencarian area hanya berbasis data gratis OpenStreetMap.

### 24.2 Dependensi

| Dependensi | Dampak jika bermasalah |
| --- | --- |
| Hosting dan MySQL (Hostinger) | Seluruh aplikasi berhenti. Perlu cadangan (`mysqldump` terjadwal) |
| Pusher (paket gratis) | Chat dan notifikasi tidak real-time. Cadangan polling Livewire |
| Pengiriman email (reset password, verifikasi) | Reset password tidak berfungsi. Lihat risiko R3 |
| Layanan peta OpenStreetMap | Peta tidak tampil. Alamat teks tetap tersedia |
| Indikator inspeksi dari tim | Tanpa itu template sementara dipakai |
| Domain dan sertifikat HTTPS | Kamera browser tidak dapat dipakai tanpa HTTPS |

---

## 25. Risiko dan Catatan Teknis

| # | Risiko | Dampak | Mitigasi |
| --- | --- | --- | --- |
| R1 | **Waktu sangat sempit** (P0 dalam 4 hari, 3 orang) | Fitur tidak selesai atau berkualitas rendah | Disiplin prioritas P0/P1/P2, form berbasis template, pembayaran simulasi sederhana, hindari fitur di luar rencana |
| R2 | **Jenis hosting tidak cocok** dengan stack | Pengerjaan mundur atau harus diulang | Stack sudah disesuaikan dengan hosting (Laravel + MySQL). Verifikasi PHP 8.3, cron, SSH, dan koneksi Pusher di hPanel sebelum 8 Oktober |
| R3 | **Batas pengiriman email** pada layanan bawaan penyedia autentikasi sangat rendah | Reset password dan verifikasi email gagal saat demo | Matikan verifikasi email di P0. Siapkan SMTP khusus (penyedia email gratis) sebelum P1. Sediakan akun demo yang sudah aktif |
| R4 | **Foto inspeksi dapat dimanipulasi** (bukan dari kamera) | Kepercayaan terhadap hasil menurun | Komponen kamera dalam halaman, cap waktu server, tinjauan admin acak |
| R5 | **Kebocoran lokasi persis, nomor rangka, atau data inspektor** | Risiko privasi dan penilaian keamanan | Policy Laravel, resource/respons terpisah publik dan privat, uji hak akses |
| R6 | **Perhitungan uang salah** (komisi, pembagian, pengembalian) | Laporan keuangan tidak konsisten, juri mempertanyakan | Ledger tunggal, aturan pembulatan 8.1, uji perhitungan, nilai disalin pada job |
| R7 | **Race condition** pada ambil job dan pembayaran | Dua inspektor di satu job, tagihan ganda | Pembaruan atomik berkondisi, kolom virtual dengan indeks unik, `lockForUpdate()`, *idempotency key* |
| R8 | **Rating hanya setelah transaksi** membuat banyak inspektor tidak pernah dinilai | Sistem reputasi tidak berjalan | Usulan tambahan rating oleh pemohon inspeksi [OPEN] |
| R9 | **Nama "jaminan"** pada hasil inspeksi menimbulkan klaim tanggung jawab | Risiko hukum pada produksi | Disclaimer jelas, rating, dan syarat ketentuan. Konsultasi hukum sebelum rilis komersial |
| R10 | **Dana ditahan dan komisi** pada produksi memerlukan penyedia pembayaran berizin | Tidak dapat dioperasikan secara komersial sebagaimana prototype | Tetap simulasi untuk lomba. Pada roadmap gunakan payment gateway berizin |
| R11 | **Cadangan data** tidak tersedia pada paket gratis | Kehilangan data | Ekspor terjadwal, uji pemulihan sekali |
| R12 | **Pasar dua sisi** (kurang inspektor atau kurang listing) | Job menumpuk atau listing sepi | Data demo cukup untuk lomba. Pilot pada komunitas mekanik di Pekanbaru |
| R13 | **Kerangka SaaS** terlalu dini | Menambah kompleksitas | Tidak untuk MVP. Catat di roadmap |
| R14 | **Chat real-time** menambah kompleksitas (langganan, sambung ulang, otorisasi kanal) di tengah waktu sempit | Fitur P1 terlambat atau tidak stabil saat demo | Jadwalkan di P1 setelah P0 stabil. Pakai Pusher paket gratis, bukan server WebSocket sendiri (Reverb tidak dapat diandalkan di Hostinger Cloud). Siapkan cadangan polling Livewire bila Pusher gagal |
| R15 | **Komisi 3% dari harga jual** jauh lebih besar daripada tarif inspeksi | Penjual atau pembeli keberatan, juri mempertanyakan keadilan model | Tampilkan rincian potongan sebelum persetujuan. Siapkan alasan bisnisnya (insentif inspektor jangka panjang) untuk presentasi |
| R16 | **Konflik kepentingan inspektor**: komisi 3% hanya cair bila kendaraan terjual, sehingga inspektor terdorong menilai terlalu baik. Inspektor juga dapat berkolusi dengan penjual | Hasil inspeksi bias dan kepercayaan pembeli turun | Larangan inspeksi atas listing sendiri (8.3), rating, tinjauan admin acak atas hasil, komisi tidak dikaitkan dengan skor. Kebijakan lanjutan [OPEN] |

---

## 26. Open Questions

Urutan berdasarkan dampak pada pengerjaan 8 Oktober. Pertanyaan yang sudah dijawab tim pada v1.1 (biaya akses, persentase komisi transaksi, chat real-time, notifikasi) tidak dicantumkan lagi.

### Prioritas tinggi (jawab hari ini, 5 Oktober)

1. ~~Jenis hosting~~ **Terjawab:** Hostinger Cloud Startup, stack Laravel + MySQL. Masih perlu verifikasi kemampuan paket di hPanel (versi PHP, SSH, cron, koneksi keluar).
2. **Konfirmasi status pendaftaran lomba.** Pendaftaran sudah ditutup 4 Oktober.
3. **Pembagian peran** tiga orang dan jam kerja per hari.
4. **Indikator inspeksi** mobil dan motor dari tim. Dibutuhkan paling lambat 6 Oktober.
5. **Komisi transaksi 3%** dari harga jual: dipotong dari dana penjual (usulan) atau ditambahkan di atas harga untuk pembeli? Apakah komisi platform 10% juga berlaku atas komisi itu (bersih 2,7%)?

### Aturan bisnis

6. Apakah platform memungut komisi sendiri dari transaksi, selain 10% dari pendapatan inspektor? Usulan: tidak ada.
7. Jika satu listing memiliki beberapa inspeksi, komisi 3% diberikan ke inspektor yang mana? Usulan: inspeksi terbaru yang berlaku.
8. **Kebijakan pembatalan dan pengembalian** pada Bagian 8.4 dapat diterima, termasuk kedaluwarsa tagihan 30 menit?
9. **Kapan job Selesai dan fee cair**: setelah pemohon menekan Terima Hasil (usulan), otomatis, atau setelah admin?
10. Apakah rating juga boleh diberikan oleh **pemohon inspeksi** setelah menerima hasil? Saat ini rating hanya setelah pembeli deal lewat aplikasi, sehingga inspektor yang inspeksinya tidak berujung penjualan tidak pernah dinilai.
11. **Lencana publik**: apakah skor ringkas dan rekomendasi boleh tampil di listing, atau hanya tulisan "Sudah Diinspeksi"?
12. **Masa berlaku hasil inspeksi**: selama kendaraan belum terjual (jawaban tim). Perlu batas usia (misalnya 30 hari) dan inspeksi ulang?
13. ~~Apakah perubahan field inti listing setelah inspeksi mencabut lencana atau perlu persetujuan admin?~~ **Terjawab:** field inti → `pending_review` + lencana dicabut; field inti terkunci saat job/transaksi aktif (D-05).
14. Apakah inspektor boleh membeli atau menjual dengan akun yang sama? Sementara ini dilarang (Bagian 10).
15. **Konflik kepentingan inspektor** (R16): apakah larangan inspeksi atas listing sendiri dan tinjauan admin acak cukup, atau perlu aturan lain?

### Teknis dan produk

16. **Verifikasi email**: dinyalakan atau dimatikan pada P0?
17. Maksud backup **"7 hari"**: retensi cadangan 7 hari?
18. **Kondisi kerja inspektor** di lapangan (sinyal, kamera HP), dan jumlah foto minimal per inspeksi.
19. **Daftar merek dan model** untuk dropdown, atau input bebas?
20. **Kepanjangan SIMOKAS**, logo, dan palet warna yang akan dipakai.
21. **Target performa dan metrik sukses** pada Bagian 3 dan 20.1. Setuju atau ada angka lain?
22. **Masa simpan data** dan kebijakan penghapusan akun.
23. **Kebijakan privasi dan syarat ketentuan**: siapa yang menyusun dan menelaah?
24. **Kejadian pemicu notifikasi** di web: apakah daftar pada 13.15 sudah sesuai?
25. **Nomor rangka** pada form listing: wajib atau opsional, dan apakah perlu dicocokkan dengan hasil inspeksi secara otomatis?

---

## 27. Rekomendasi MVP

### 27.1 P0 (deploy 8 Oktober)

Bangun satu alur yang dapat didemokan penuh:

1. Registrasi dan login tiga role, dengan akun admin seed.
2. Listing: tambah, edit, foto, pin lokasi, validasi admin.
3. Daftar barang dan detail listing dengan filter.
4. Pendaftaran dan verifikasi inspektor.
5. Ajukan inspeksi, pembayaran simulasi, dan ledger.
6. Daftar job, ambil job, jadwal, mulai inspeksi.
7. Form inspeksi berbasis template dengan foto kamera bertimestamp.
8. Hasil inspeksi dengan akses terkunci dan akses berbayar.
9. Dashboard dan pengaturan admin (tarif, komisi).

### 27.2 P1 (sebelum 12 Oktober)

* Transaksi dalam platform (pembayaran simulasi).
* Chat real-time.
* Rating dan peringkat inspektor (setelah pembeli deal lewat aplikasi).
* Notifikasi di dalam web.
* Kebijakan privasi dan syarat ketentuan.
* Riwayat lengkap dan pemolesan UI.

### 27.3 P2 (setelah lomba)

* Mode gelap.
* Audit log dan analitik pemakaian.
* Pelaporan dan sanggahan hasil inspeksi.
* Pembatasan job berdasarkan rating.
* Tarik saldo simulasi.
* Ekspor laporan.
* Notifikasi email.
* Perluasan wilayah.

### 27.4 Roadmap SaaS (dicatat, tidak dikerjakan)

Multi-tenant untuk dealer, showroom, bengkel, dan leasing. Payment gateway berizin, penyelesaian sengketa, perluasan kota dan jenis kendaraan, serta aplikasi mobile.

### 27.5 Prinsip Pengambilan Keputusan Selama Pengerjaan

Jika waktu habis, korbankan **kelengkapan fitur P1**, bukan **kualitas alur P0** dan **tampilan**. Penilaian lomba memberi bobot besar pada fungsionalitas, UI/UX, dan kualitas teknis. Satu alur yang mulus lebih bernilai daripada banyak fitur yang setengah jadi.

---

## 28. Acceptance Criteria

### 28.1 Registrasi dan Login (P0)

- [x] Pengguna dapat mendaftar sebagai pengguna umum dengan data valid dan langsung masuk ke dashboard.
- [x] Email atau nomor HP tidak valid ditolak dengan pesan pada field terkait.
- [x] Inspektor yang mendaftar berstatus Menunggu Verifikasi dan tidak dapat membuka daftar job.
- [x] Login gagal tidak menyebutkan apakah email atau password yang salah.
- [x] Reset password lewat tautan email berfungsi.
- [x] Pengguna tidak dapat mendaftar sebagai admin dari halaman publik.

### 28.2 Listing (P0)

- [x] Penjual dapat membuat listing dengan 3–10 foto, titik lokasi, dan opsi waktu inspeksi.
- [x] Listing baru berstatus Menunggu Validasi dan tidak tampil di daftar publik.
- [x] Admin dapat menyetujui atau menolak (dengan alasan), dan status berubah sesuai.
- [x] Pin di luar Pekanbaru ditolak.
- [x] Respons publik tidak memuat koordinat persis, alamat lengkap, maupun nomor rangka.
- [x] Daftar barang dapat disaring dan diurutkan sesuai Bagian 13.2.

### 28.3 Inspeksi dan Pembayaran (P0)

- [ ] Tombol Ajukan Inspeksi hanya muncul pada listing Aktif tanpa job aktif.
- [x] Pembayaran simulasi sukses mengubah job menjadi Dibayar dan listing menjadi Dalam Inspeksi.
- [x] Klik ganda pada Bayar tidak menghasilkan tagihan ganda.
- [x] Pembayaran gagal tidak membuat job tampil di daftar inspektor.
- [x] Tagihan yang tidak dibayar dalam 30 menit berstatus Kedaluwarsa dan listing dapat diajukan inspeksi lagi.
- [x] Tarif yang dikenakan sama dengan tarif admin saat job dibuat (default Rp15.000 motor, Rp50.000 mobil).

### 28.4 Job Inspektor (P0)

- [x] Inspektor terverifikasi melihat job terbuka. Yang belum terverifikasi tidak.
- [x] Ambil Job hanya berhasil untuk satu inspektor bila ditekan bersamaan.
- [x] Alamat persis dan nomor rangka hanya muncul setelah job diambil.
- [x] Inspektor tidak dapat mengambil job atas listing miliknya sendiri.
- [x] Pembatalan oleh inspektor mengembalikan job ke daftar.

### 28.5 Form dan Hasil Inspeksi (P0)

- [x] Template mobil dan motor berbeda tampil sesuai jenis kendaraan.
- [x] Draf tersimpan dan dapat dilanjutkan setelah halaman dimuat ulang.
- [x] Kirim Hasil ditolak jika item wajib atau foto minimal belum terpenuhi, dengan daftar yang kurang.
- [x] Foto inspeksi hanya dapat diambil dari kamera dan menampilkan timestamp.
- [x] Skor dan rekomendasi dihitung sesuai Bagian 15.3, termasuk aturan item kritis.
- [x] Laporan terkunci setelah dikirim.

### 28.6 Akses Hasil dan Pendapatan (P0)

- [x] Listing yang sudah diinspeksi menampilkan lencana publik, tetapi rincian hasil tersembunyi.
- [x] Pemilik, inspektor pembuat, pembayar inspeksi, dan admin dapat melihat rincian tanpa biaya akses.
- [x] Pengguna lain melihat tombol Buka Hasil dengan biaya akses yang sama dengan tarif inspeksi, dan setelah membayar dapat melihat hasil.
- [x] Pembayaran akses menambah pendapatan inspektor dikurangi komisi platform 10%, dan tercatat di ledger. Jumlah pendapatan inspektor dan komisi platform selalu sama dengan nilai pembayaran.
- [x] Setelah listing Terjual, pembeli baru tidak dapat membuka hasil.

### 28.7 Dashboard dan Admin (P0)

- [x] Setiap role melihat dashboard sesuai Bagian 13.8.
- [x] Admin dapat mengubah tarif dan persentase komisi, dan job baru memakai nilai baru.
- [x] Admin dapat memverifikasi inspektor, memvalidasi listing, dan menyelesaikan atau membatalkan job.

### 28.8 Keamanan dan Performa (P0)

- [x] Pengguna role A tidak dapat membaca data privat role B melalui rute, endpoint, maupun kanal real-time.
- [x] Dokumen inspektor tidak dapat diakses tanpa autentikasi yang berhak.
- [ ] Halaman daftar barang mencapai Lighthouse mobile Performance ≥ 80.
- [ ] Alur utama berjalan di Chrome Android, Safari iPhone, dan Chrome desktop.

### 28.9 Transaksi, Chat, dan Rating (P1)

- [x] Alur Ajukan Pembelian sampai Selesai berjalan sesuai Bagian 8.6, dan listing berubah menjadi Terjual.
- [x] Penjual melihat rincian potongan sebelum menyetujui pembelian.
- [x] Komisi inspektor 3% dari harga transaksi dihitung benar, dipotong platform sesuai aturan yang disepakati, dan tercatat di ledger.
- [x] Transaksi pada listing tanpa hasil inspeksi tidak menghasilkan komisi inspektor.
- [x] Pesan terkirim muncul pada lawan bicara secara real-time (target kurang dari 2 detik) tanpa memuat ulang halaman, dengan penanda belum dibaca.
- [x] Pengguna hanya menerima pesan dari percakapan yang diikutinya.
- [x] Setelah koneksi terputus dan pulih, pesan yang terlewat muncul.
- [x] Hanya pembeli dari transaksi selesai yang dapat memberi rating, satu kali per transaksi.
- [x] Rating rata-rata inspektor diperbarui dan tampil pada hasil inspeksi dan dashboard.
- [ ] Notifikasi untuk kejadian pada 13.15 muncul real-time dan dapat ditandai dibaca.
- [x] Halaman kebijakan privasi dan syarat ketentuan dapat diakses, dan persetujuan dicatat saat registrasi.

---

## 29. Glosarium

| Istilah | Arti |
| --- | --- |
| **SIMOKAS** | Nama aplikasi (kepanjangan [OPEN]) |
| **Inspektor** | Teknisi atau mekanik terverifikasi yang melakukan inspeksi kendaraan |
| **Job inspeksi** | Pekerjaan inspeksi atas satu kendaraan yang tampil di portal *open job* |
| **Open job** | Pekerjaan yang dibuka untuk dipilih inspektor mana pun yang terverifikasi |
| **Lencana "Sudah Diinspeksi"** | Penanda publik pada listing yang sudah memiliki hasil inspeksi |
| **Biaya akses** | Biaya yang dibayar pihak lain untuk membuka hasil inspeksi, sama dengan tarif inspeksi |
| **Komisi platform** | 10% dari setiap pembayaran tarif inspeksi atau biaya akses, diambil platform. Sisanya menjadi pendapatan inspektor |
| **Komisi transaksi** | 3% dari harga jual untuk inspektor jika transaksi terjadi di platform dan listing sudah diinspeksi |
| **Ledger** | Buku catatan seluruh arus uang simulasi |
| **Dana ditahan (escrow)** | Dana pembeli yang ditahan platform sampai serah terima dikonfirmasi |
| **Template inspeksi** | Daftar kategori dan item penilaian yang disimpan sebagai data |
| **Item kritis** | Item yang jika Buruk otomatis menjadikan rekomendasi Tidak Direkomendasikan |
| **MVP** | Versi minimum yang dapat dipakai dan didemokan |
| **P0 / P1 / P2** | Tingkat prioritas pengerjaan |
| **Policy** | Kelas otorisasi Laravel yang menentukan siapa boleh melakukan aksi apa pada suatu model |
| **Idempotency key** | Kunci unik agar permintaan yang diulang tidak menghasilkan efek ganda |
| **Seed** | Data awal yang dimasukkan ke basis data untuk demo dan pengujian |
| **SaaS** | *Software as a Service*, produk aplikasi yang dilanggan pelanggan |
| **UU PDP** | Undang-Undang Nomor 27 Tahun 2022 tentang Pelindungan Data Pribadi |
| **WIB** | Waktu Indonesia Barat, zona waktu Pekanbaru |

---

## 30. Kesimpulan

SIMOKAS memadukan tiga hal yang belum bertemu di marketplace kendaraan bekas konvensional: **jaminan kualitas melalui inspeksi independen**, **portal kerja terbuka bagi teknisi bersertifikat**, dan **model pendapatan berulang** melalui akses hasil inspeksi berbayar.

Untuk lomba, kunci keberhasilannya bukan jumlah fitur, melainkan **satu alur utama yang mulus, aman, dan mudah dijelaskan**: dari penjual memasang penawaran, pembeli mengajukan inspeksi, inspektor mengambil dan mengisi laporan, hingga hasil terkunci dan dapat dibuka dengan akses berbayar. Setelah alur itu stabil pada 8 Oktober, fitur transaksi, chat, dan rating melengkapi cerita produk sebelum pengumpulan pada 12 Oktober.

**Langkah berikutnya:** jawab Open Questions prioritas tinggi (Bagian 26 poin 1–5), serahkan indikator inspeksi mobil dan motor paling lambat 6 Oktober, lalu PRD ini diperbarui ke versi 1.6.

---

## Lampiran. Catatan Perubahan v1.4

Daftar ini membantu tim meninjau apa saja yang berubah dari draf sebelumnya. Perubahan bertanda **keputusan baru** adalah usulan penyusun dan perlu disetujui tim.

### A. Perbaikan format dan struktur

| Area | Perubahan |
| --- | --- |
| Nomor versi | Header, riwayat perubahan, dan nama file diseragamkan menjadi 1.4. Sebelumnya header 1.1, nama file v1_3, dan penutup menyebut 1.2 |
| Format markdown | Tabel diganti ke format tabel baku. Daftar yang menyatu dalam satu paragraf (misalnya kebutuhan pengguna, contoh perhitungan, aturan registrasi, aturan listing) dipecah menjadi daftar yang benar. Karakter pelarian (`\[ASUMSI\]`, `\<`) dibersihkan |
| Nomor dan referensi | Penomoran bagian 1–30 dipertahankan agar referensi silang tetap berlaku. Sub-bagian 13.9a diganti menjadi 13.10 dan seterusnya |
| Non-goals | Dipindahkan dari 3.4 ke 7.2 agar tidak terpisah dari ruang lingkup |
| Kolom "Sumber" pada 8.1 | Diganti kolom "Status" (Ditetapkan tim atau [ASUMSI]) supaya tabel lebih ringkas |
| Bahasa meta | Kalimat yang membahas proses penyusunan (misalnya "PRD ini mengoreksinya") ditulis ulang menjadi pernyataan kebutuhan |

### B. Pembaruan sesuai tanggal 5 Oktober 2026

| Area | Perubahan |
| --- | --- |
| Status dokumen | Diperbarui 5 Oktober 2026 |
| Timeline | Pendaftaran ditandai sudah ditutup. Rencana harian dimulai 5 Oktober dan baris 4 Oktober dihapus karena sudah lewat |
| Open Questions | Prioritas tinggi diberi tanggal 5 Oktober. Pertanyaan "apakah tim sudah terdaftar" diubah menjadi permintaan konfirmasi status |

### C. Celah aturan bisnis yang ditutup (keputusan baru, perlu persetujuan)

| Area | Perubahan | Alasan |
| --- | --- | --- |
| Kedaluwarsa tagihan | Dari 24 jam menjadi 30 menit, dengan status `expired` | Tagihan tidak dibayar menahan satu-satunya slot job aktif pada listing, sehingga pemohon lain terblokir selama 24 jam |
| Konflik kepentingan | Inspektor tidak boleh mengambil job atas listing sendiri (8.3, E24, `403 OWN_LISTING`) | Draf sebelumnya tidak melarangnya |
| Risiko R16 | Insentif komisi 3% mendorong penilaian terlalu baik, ditambah risiko kolusi | Belum tercatat sebagai risiko |
| Peran inspektor | Sementara dilarang mengajukan inspeksi, membeli, dan menjual sampai Open Question 14 dijawab | Menghindari celah konflik kepentingan selama belum diputuskan |
| Pembulatan | Aturan pembulatan komisi platform ditambahkan di 8.1 | R6 menyebut "pembulatan konsisten" tetapi aturannya belum ada |
| Nomor rangka | Ditambahkan ke form listing dan data model, hanya terlihat oleh pemilik, inspektor job, dan admin | Draf menyebut nomor rangka sebagai field inti dan item inspeksi, tetapi tidak ada di form |
| Masa berlaku akses | "Selama listing masih aktif" diganti "selama listing belum Terjual" | Status "Aktif" ambigu karena listing bisa Dalam Inspeksi atau Dalam Transaksi |
| Nonaktifkan listing | Larangan berlaku untuk menghapus dan menonaktifkan listing yang memiliki job aktif | 13.3 hanya menyebut menghapus, sedangkan E6 menyebut keduanya |
| Istilah uang | "Komisi platform" (10%) dan "komisi transaksi" (3%) didefinisikan terpisah di 8.1 dan glosarium | Sebelumnya mudah tertukar |

### D. Kelengkapan spesifikasi

| Area | Perubahan |
| --- | --- |
| Kebutuhan fungsional | Ditambahkan 13.12 Transaksi, 13.13 Chat, 13.14 Rating, dan 13.15 Notifikasi. Sebelumnya fitur P1 ini hanya muncul di aturan bisnis dan acceptance criteria |
| State machine | Ditambahkan status `expired` dan aturan transisi job yang lengkap, serta transisi `rejected → pending_review` untuk listing |
| Data model | Ditambahkan `chassis_number`, `expires_at`, `platform_fee_pct`, `needs_attention_at` pada job, dan `is_hidden` pada rating |
| API | Ditambahkan endpoint dokumen inspektor, detail dan daftar job milik sendiri, template, pendapatan, notifikasi, penyelesaian dan pembatalan job oleh admin, serta blokir pengguna. Ditambahkan kode `410 PAYMENT_EXPIRED` dan `403 OWN_LISTING` |
| Acceptance criteria | Ditambahkan kriteria untuk kedaluwarsa tagihan, larangan inspeksi listing sendiri, nomor rangka, pembulatan, dan notifikasi |
| Akun demo | Ditambahkan aturan menonaktifkan akun demo setelah lomba |
| Open Questions | Ditambahkan pertanyaan 15 (konflik kepentingan), 24 (pemicu notifikasi, disesuaikan), dan 25 (nomor rangka) |

### E. Perubahan v1.5 (penggantian stack)

| Area | Perubahan |
| --- | --- |
| Stack (6.2) | Next.js + Supabase + Vercel diganti Laravel 13 + Blade/Livewire/Tailwind + MySQL di Hostinger Cloud Startup |
| Database | PostgreSQL tidak tersedia di paket Cloud Hostinger dan PostgreSQL eksternal menambah latensi. Dipilih MySQL. Indeks unik parsial diganti kolom virtual `active_listing_id` + indeks unik. `timestamptz` menjadi `timestamp` (UTC), `text[]` dan `jsonb` menjadi `json`, enum menjadi string |
| Hak akses | Row Level Security diganti Policy/Gate Laravel. Tabel `profiles` digabung ke `users` |
| Real-time | Supabase Realtime diganti Laravel Echo + Pusher, dengan cadangan polling Livewire |
| Penyimpanan | Supabase Storage diganti disk privat Laravel dan tautan bertanda tangan sementara |
| Tugas terjadwal | Kedaluwarsa tagihan dan penandaan job memakai Laravel Scheduler lewat cron hPanel |
| Tidak berubah | Seluruh aturan bisnis (Bagian 8), alur pengguna (12), status (14), template inspeksi (15), dan acceptance criteria (28) |



### F. Perubahan v1.6

| Area | Perubahan |
| --- | --- |
| 6.2 | Framework diseragamkan ke Laravel 13. |
| 8.1 | Komisi 3% dari penjual dan platform 10% darinya **[ASUMSI]**; contoh 100 juta: 3 juta / 300 ribu / 2,7 juta / 97 juta. |
| 8.3, 8.5, 14.2 | D-01/D-03: timeout 7 hari dari `available_since`; auto-complete 3 hari (`auto_accept_days`). |
| 8.6, 13.12, 14.3 | D-02/D-04: pembayaran ditunda; pembatalan; timeout 48 jam / 24 jam / 7 hari. |
| 13.3, Q13 | D-05: non-inti langsung; inti → `pending_review`, lencana dicabut, field inti terkunci. |
| 18.7, 18.17 | Tambah `available_since`, `auto_accept_days`=3, `paid_job_timeout_days`=7; komisi 3% dan 10% tetap. |
| 22 | Edge case tambahan dari flow. |
| 10 | D-06 tetap **[OPEN]**, default dilarang. |
| 9 | Tautan panduan inspektor. |
