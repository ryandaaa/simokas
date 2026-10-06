# Daftar Periksa Uji Manual SIMOKAS

Dokumen ini adalah daftar periksa untuk QA-P0. Pengujian dilakukan oleh manusia pada perangkat nyata dan browser desktop. Setiap langkah memiliki sumber agar temuan dapat ditelusuri kembali ke aturan produk.

## 1. Persiapan

| No | Persiapan | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 1 | Catat alamat aplikasi yang akan diuji sebelum mulai. | Alamat aplikasi tersedia. Jika belum ditetapkan, tulis `[perlu dikonfirmasi]`. | QA-P0 | [ ] | |
| 2 | Siapkan akun pembeli/penjual, inspektor terverifikasi, inspektor menunggu verifikasi, dan admin dari `docs/DEMO.md`. | Akun demo tersedia untuk setiap role. Saat ini `docs/DEMO.md` belum ada: `[perlu dikonfirmasi]`. | PRD 23.4 | [ ] | |
| 3 | Siapkan Chrome Android. | Alur utama dapat dijalankan tanpa bantuan developer. | PRD 23.3, 28.8 | [ ] | |
| 4 | Siapkan Safari iPhone. | Alur utama dapat dijalankan tanpa bantuan developer. | PRD 23.3, 28.8 | [ ] | |
| 5 | Siapkan Chrome desktop. | Alur utama dapat dijalankan tanpa bantuan developer. | Issue #33, PRD 23.3 | [ ] | |
| 6 | Uji tampilan pada lebar 360 px, 768 px, dan 1280 px. | Tidak ada layout rusak, aksi utama tetap dapat digunakan. | UI-12 | [ ] | |
| 7 | Pastikan data demo mencakup listing mobil/motor, job terbuka, job berjalan, dan hasil inspeksi. | Data cukup untuk menjalankan alur P0 dan P1. | PRD 23.4 | [ ] | |

## 2. Pendaftaran dan masuk

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 8 | Daftar sebagai pengguna dengan data valid. | Akun dibuat dan masuk ke dashboard. | AUTH-T01, PRD 28.1 | [ ] | |
| 9 | Masukkan email atau nomor HP yang tidak valid. | Galat muncul pada field terkait. | AUTH-T02, PRD 28.1 | [ ] | |
| 10 | Coba password kurang dari aturan minimum. | Registrasi ditolak dengan galat yang jelas. | AUTH-T03 | [ ] | |
| 11 | Coba memanipulasi pilihan role menjadi admin pada pendaftaran publik. | Akun tidak menjadi admin. | AUTH-E2, AUTH-T05 | [ ] | |
| 12 | Daftar sebagai inspektor dengan data dan dokumen yang diperlukan. | Akun berstatus menunggu verifikasi. | AUTH-T04, ONB-R1 | [ ] | |
| 13 | Coba login dengan email benar dan password salah, lalu sebaliknya. | Pesan gagal tetap generik dan tidak membocorkan field mana yang salah. | AUTH-R6, AUTH-T07 | [ ] | |
| 14 | Coba login berulang sampai rate limit tercapai. | Sistem menolak sementara dengan respons 429/pesan tunggu. | AUTH-E3, AUTH-T08 | [ ] | |
| 15 | Login dengan akun yang diblokir. | Login ditolak dan akun diberi informasi bahwa akun diblokir. | AUTH-E4, AUTH-T09 | [ ] | |
| 16 | Gunakan alur lupa password. | Tautan reset dapat digunakan; tautan kedaluwarsa atau sudah dipakai ditolak dengan pesan jelas. | AUTH-T10, AUTH-E6 | [ ] | |
| 17 | Coba akses halaman role lain sebagai role yang tidak berhak. | Akses mendapat 403 atau diarahkan sesuai aturan. | AUTH-E8, AUTH-T12 | [ ] | |

## 3. Penjual membuat dan mengedit listing

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 18 | Buat listing dengan data valid, 3–10 foto, lokasi Pekanbaru, dan opsi waktu inspeksi. | Listing dibuat dengan status Menunggu Validasi dan belum tampil publik. | PRD 28.2, LST-T01 | [ ] | |
| 19 | Masukkan titik lokasi di luar Pekanbaru. | Listing ditolak dengan galat lokasi. | PRD 28.2 | [ ] | |
| 20 | Edit field non-inti seperti harga, deskripsi, foto, warna, slot, atau tempat inspeksi. | Perubahan berlaku langsung sesuai aturan non-inti. | PRD 13.3, D-05 | [ ] | |
| 21 | Setelah listing sudah pernah diinspeksi, ubah field inti: jenis, merek, model, tahun, nomor rangka, atau kilometer. | Listing menjadi `pending_review`, lencana inspeksi dicabut, dan hasil lama tetap ada di riwayat pembayar. | D-05, PRD 13.3 | [ ] | |
| 22 | Coba mengubah field inti ketika job aktif atau transaksi berjalan. | Perubahan ditolak karena field inti terkunci. | LST-R7, TRX-E14 | [ ] | |
| 23 | Coba menonaktifkan listing saat ada job aktif atau transaksi. | Aksi ditolak. | LST-E4, LST-T09 | [ ] | |
| 24 | Buka listing yang sedang menunggu validasi dari akun publik. | Listing tidak dapat dibuka sebagai listing publik. | CAT-E4, LST-E10 | [ ] | |

## 4. Admin memvalidasi listing dan inspektor

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 25 | Login sebagai admin dan buka antrean validasi listing. | Listing menunggu validasi terlihat. | ADM-R1, PRD 12.8 | [ ] | |
| 26 | Setujui listing. | Status menjadi Aktif dan listing dapat tampil publik. | ADM-T02, PRD 28.2 | [ ] | |
| 27 | Tolak listing tanpa alasan. | Aksi ditolak karena alasan wajib. | ADM-R4, ADM-T02 | [ ] | |
| 28 | Tolak listing dengan alasan. | Status menjadi Ditolak dan alasan tersedia bagi penjual. | ADM-T02 | [ ] | |
| 29 | Buka antrean verifikasi inspektor. | Pengajuan dengan dokumen lengkap dapat ditinjau. | ONB-R4, ONB-T03 | [ ] | |
| 30 | Setujui inspektor. | Status menjadi approved; inspektor dapat melihat job sesuai keahlian. | ONB-T04, ONB-R5 | [ ] | |
| 31 | Tolak inspektor tanpa alasan. | Aksi ditolak. | ONB-T05, ADM-R4 | [ ] | |
| 32 | Tolak dengan alasan lalu ajukan ulang. | Status kembali pending dan alasan lama tetap sesuai riwayat yang tersedia. | ONB-E5, ONB-T06 | [ ] | |
| 33 | Sebagai admin, buka dokumen inspektor. | Dokumen dapat dibuka oleh admin. | ONB-R2, ONB-T07 | [ ] | |

## 5. Pembeli mencari dan melihat listing

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 34 | Buka daftar barang sebagai tamu. | Hanya listing Aktif, Dalam Inspeksi, atau Dalam Transaksi yang tampil. | CAT-R1, CAT-T01 | [ ] | |
| 35 | Gunakan pencarian, filter, dan pengurutan. | Hasil sesuai filter dan urutan yang dipilih. | CAT-R2, CAT-R3, CAT-T02, CAT-T03 | [ ] | |
| 36 | Masukkan parameter pencarian/filter yang tidak valid. | Parameter diabaikan/dikoreksi; tidak ada 500. | CAT-E2, CAT-T05 | [ ] | |
| 37 | Buka detail listing sebagai tamu. | Hanya data publik: foto, spesifikasi, harga, area, lencana, dan peta perkiraan. | CAT-R5, CAT-R7 | [ ] | |
| 38 | Periksa HTML/data klien detail publik. | Tidak ada `lat`, `lng`, alamat lengkap, nomor rangka, atau `inspection_place` privat. | CAT-T06, PRD 28.2 | [ ] | |
| 39 | Buka listing non-publik melalui URL langsung sebagai pengguna lain. | 404 sesuai aturan visibilitas. | CAT-E4, CAT-T08 | [ ] | |
| 40 | Buka listing Terjual. | Ditampilkan sebagai Terjual tanpa tombol aksi pembelian/inspeksi. | CAT-E5, CAT-T09 | [ ] | |

## 6. Ajukan inspeksi dan bayar simulasi

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 41 | Pada listing Aktif tanpa job aktif, tekan Ajukan Inspeksi. | Tombol tersedia dan sistem menampilkan kendaraan, tarif, serta opsi waktu/tempat. | CAT, JOB-R1, PRD 13.4 | [ ] | |
| 42 | Coba Ajukan Inspeksi pada listing yang sudah punya job aktif. | Aksi tidak tersedia atau ditolak dengan konflik job aktif. | JOB-R1, JOB-E2 | [ ] | |
| 43 | Buka pembayaran simulasi. | Halaman jelas diberi label SIMULASI dan nominal berasal dari server. | PAY-R1, PAY-R9 | [ ] | |
| 44 | Pilih hasil pembayaran Berhasil. | Payment sukses, job menjadi Dibayar, listing menjadi Dalam Inspeksi. | PRD 28.3, JOB flow | [ ] | |
| 45 | Klik Bayar dua kali atau ulangi request yang sama. | Hanya satu tagihan/pembayaran tercipta. | PAY-E1, PAY-T03, PRD 28.3 | [ ] | |
| 46 | Pilih hasil pembayaran Gagal. | Job tetap requested dan tidak muncul sebagai job terbuka. | PAY-E2, PAY-T05 | [ ] | |
| 47 | Biarkan tagihan melewati 30 menit atau uji pada data yang sudah kedaluwarsa. | Tagihan Kedaluwarsa; listing dapat diajukan inspeksi lagi. | PAY-E4, PAY-T06, PRD 28.3 | [ ] | |
| 48 | Ubah tarif admin setelah job/tagihan dibuat lalu cek nilai job lama. | Job/tagihan lama tetap memakai nilai yang disalin. | JOB-R6, PAY-E8, ADM-E1 | [ ] | |

## 7. Inspektor mengambil dan menjalankan job

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 49 | Login sebagai inspektor pending dan buka daftar job. | Akses ditolak. | ONB-E4, ONB-T08 | [ ] | |
| 50 | Login sebagai inspektor approved dan buka daftar job. | Job yang sesuai keahlian tampil. | ONB-R5, ONB-T11, JOB-R3 | [ ] | |
| 51 | Ambil satu job. | Job menjadi Diambil dan alamat persis/nomor rangka baru terlihat oleh inspektor tersebut. | JOB-R2, JOB-R5, PRD 28.4 | [ ] | |
| 52 | Dua inspektor menekan Ambil Job pada job yang sama. | Tepat satu berhasil; yang lain mendapat konflik JOB_ALREADY_TAKEN. | JOB-E1, JOB-T01, PRD 28.4 | [ ] | |
| 53 | Coba mengambil job dari listing milik sendiri. | Ditolak 403 OWN_LISTING. | JOB-R4, JOB-E5 | [ ] | |
| 54 | Ambil sampai batas 3 job aktif. | Job keempat ditolak dengan MAX_ACTIVE_JOBS. | JOB-R3, JOB-E6 | [ ] | |
| 55 | Jadwalkan inspeksi dengan penjual. | Status menjadi Dijadwalkan setelah jadwal final ditetapkan. | JOB flow, PRD 12.3 | [ ] | |
| 56 | Tekan Mulai Inspeksi sebelum jadwal ditetapkan. | Aksi ditolak dan status tidak berubah. | JOB-E10 | [ ] | |
| 57 | Mulai inspeksi setelah jadwal tersedia. | Status menjadi Sedang Diinspeksi. | JOB flow | [ ] | |
| 58 | Jangan tetapkan jadwal selama 48 jam pada job yang diambil. | Job ditandai perlu tindakan. | JOB-R10, JOB-E4 | [ ] | |
| 59 | Batalkan job sebelum inspeksi dimulai. | Job kembali Dibayar dan tersedia untuk inspektor lain. | PRD 28.4, JOB-E3 | [ ] | |
| 60 | Pastikan job yang kembali tersedia memiliki penghitung 7 hari yang dimulai ulang. | Batas 7 hari dihitung dari `available_since` terbaru. | JOB-E3, D-01 | [ ] | |

## 8. Checklist, draf, dan kamera

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 61 | Buka form inspeksi mobil. | Template mobil tampil. | RES-T01, PRD 28.5 | [ ] | |
| 62 | Buka form inspeksi motor. | Template motor berbeda sesuai jenis kendaraan. | RES-T01 | [ ] | |
| 63 | Isi beberapa kategori lalu pindah halaman/reload. | Draf tersimpan dan dapat dilanjutkan. | RES-R2, RES-E3, RES-E4 | [ ] | |
| 64 | Isi item wajib tetapi belum memenuhi foto minimal. | Kirim Hasil ditolak dan kekurangan disebutkan. | RES-T03, PRD 28.5 | [ ] | |
| 65 | Tolak izin kamera browser/perangkat. | Sistem memberi petunjuk mengaktifkan izin; hasil tidak dapat dikirim bila foto wajib belum ada. | RES-E2 | [ ] | |
| 66 | Coba memilih foto dari galeri. | Foto ditolak dengan NOT_CAMERA_CAPTURE. | RES-R5, RES-E5, RES-T04 | [ ] | |
| 67 | Ambil foto langsung dari kamera. | Foto diterima dan memiliki cap waktu; waktu server menjadi acuan. | RES-R6, RES-T04, PRD 28.5 | [ ] | |
| 68 | Uji foto lebih dari 2 MB. | Sistem mengompresi otomatis; bila tetap tidak memenuhi batas, tampil galat yang jelas. | RES-E1, RES-T05 | [ ] | |
| 69 | Uji orientasi portrait/landscape pada form dan kamera. | Konten tetap dapat dibaca dan aksi tetap dapat digunakan. | QA-P0 §3, UI-12 | [ ] | |
| 70 | Ketik pada field di HP hingga keyboard terbuka. | Keyboard tidak menutupi field atau tombol yang sedang digunakan. | QA-P0 §3 | [ ] | |
| 71 | Pindah halaman saat draf belum selesai disimpan. | Draf tetap aman di server dan dapat dilanjutkan. | RES-E3, RES-E4 | [ ] | |

## 9. Kirim hasil, akses hasil, dan pendapatan

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 72 | Lengkapi semua item wajib dan foto minimal, lalu Kirim Hasil. | Hasil diterima, skor/rekomendasi dihitung, dan hasil terkunci. | RES-R3, RES-R4, PRD 28.5 | [ ] | |
| 73 | Klik Kirim Hasil dua kali. | Efek hanya sekali. | RES-E6, RES-T09 | [ ] | |
| 74 | Setelah hasil terkirim, coba ubah sebagai inspektor. | Perubahan ditolak. | RES-R4, RES-T08 | [ ] | |
| 75 | Sebagai admin, buka kembali hasil terkunci. | Status kembali draft, tercatat, dan inspektor mendapat notifikasi. | RES-E14, RES-T08, ADM-T09 | [ ] | |
| 76 | Sebagai pemohon, tekan Terima Hasil. | Job menjadi Selesai dan fee masuk ke saldo inspektor sesuai ledger. | PRD 12.3, JOB-R9 | [ ] | |
| 77 | Jangan tekan Terima Hasil selama 3 hari. | Job otomatis selesai setelah `auto_accept_days`. | JOB-R9, PRD 12.3 | [ ] | |
| 78 | Buka hasil sebagai pemilik listing, inspektor pembuat, pemohon, dan admin. | Masing-masing dapat melihat rincian tanpa biaya akses. | PRD 28.6, RES-T10 | [ ] | |
| 79 | Buka hasil sebagai pengguna lain tanpa akses. | Hanya ringkasan terbatas dan tombol Buka Hasil dengan biaya akses. | RES-R8, RES-E12, RES-T11 | [ ] | |
| 80 | Bayar akses hasil. | Akses tercatat, hasil terbuka, dan pembagian ledger sesuai tarif platform. | RES-T12, PAY-T08, PRD 28.6 | [ ] | |
| 81 | Buka akses hasil yang sama untuk kedua kali. | Tidak dibuat tagihan kedua. | RES-E7, RES-T12, PAY-T15 | [ ] | |
| 82 | Setelah listing Terjual, buka hasil sebagai pembeli baru. | Pembeli baru tidak dapat membuka; pembayar lama tetap dapat melihat riwayat. | RES-R11, RES-E8, RES-T13 | [ ] | |
| 83 | Buka halaman Pendapatan inspektor. | Saldo dan rincian sumber pendapatan dapat dilihat. | DSH-R3, PRD 13.9 | [ ] | |

## 10. Transaksi jual beli (P1)

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 84 | Sebagai pembeli, ajukan pembelian pada listing yang memenuhi syarat. | Penawaran dibuat dan penjual melihat harga, komisi, serta jumlah diterima. | TRX-R1, TRX-R4, TRX-T01, PRD 28.9 | [ ] | |
| 85 | Coba membeli listing sendiri atau sebagai inspektor. | Aksi ditolak. | TRX-E1, TRX-R10, TRX-T02 | [ ] | |
| 86 | Dua pembeli mengajukan pembelian bersamaan. | Hanya satu transaksi berjalan; yang lain mendapat konflik. | TRX-E2, TRX-T03 | [ ] | |
| 87 | Penjual mengubah harga setelah penawaran dibuat. | Penawaran tetap memakai harga yang tersalin. | TRX-E3, TRX-T05 | [ ] | |
| 88 | Setujui transaksi saat job inspeksi masih aktif. | Status accepted; pembayaran belum dibuka sampai job final. | TRX-E4, TRX-T07 | [ ] | |
| 89 | Selesaikan job inspeksi lalu buka kembali transaksi accepted. | Pembayaran terbuka dan penghitung 24 jam dimulai. | TRX-E5, TRX-R9 | [ ] | |
| 90 | Bayar transaksi dua kali/klik ganda. | Satu pembayaran; dana berstatus paid_held dan listing menjadi in_transaction. | TRX-E6, TRX-T08 | [ ] | |
| 91 | Konfirmasi serah terima. | Dana diteruskan, ledger benar, listing menjadi sold, akses hasil baru ditutup, rating terbuka. | TRX-T09, TRX-R7, TRX-R11 | [ ] | |
| 92 | Konfirmasi serah terima dua kali. | Efek hanya sekali. | TRX-E13, TRX-T15 | [ ] | |
| 93 | Penawaran offered melewati 48 jam. | Status expired dan listing tetap bebas. | TRX-E7, TRX-T11 | [ ] | |
| 94 | accepted melewati 24 jam sejak pembayaran dibuka. | Status expired secara idempoten. | TRX-R9, TRX-T11 | [ ] | |
| 95 | paid_held melewati 7 hari tanpa serah terima. | Hanya ditandai perlu tindakan; tidak otomatis selesai/batal. | TRX-E10, TRX-T12 | [ ] | |
| 96 | Uji pembatalan sesuai peran dan status yang tersedia. | Hak, refund, status listing, dan ledger sesuai D-04; komisi inspektor tidak dibayar bila batal. | TRX-R8, TRX-T10 | [ ] | |

## 11. Chat (P1)

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 97 | Mulai chat listing antara pembeli dan penjual. | Percakapan dibuat satu kali dan dapat dibuka kembali. | CHT-R1, CHT-T01 | [ ] | |
| 98 | Kirim pesan teks valid. | Pesan tersimpan dan muncul pada lawan bicara. | CHT-T02 | [ ] | |
| 99 | Kirim pesan kosong atau lebih dari 1.000 karakter. | Validasi gagal. | CHT-E4, CHT-T04 | [ ] | |
| 100 | Kirim HTML/script sebagai pesan. | Ditampilkan sebagai teks, bukan dieksekusi. | CHT-E5, CHT-T05 | [ ] | |
| 101 | Klik Kirim dua kali dengan kunci pesan yang sama. | Satu pesan saja. | CHT-E6, CHT-T08 | [ ] | |
| 102 | Coba membaca/mengirim percakapan sebagai bukan peserta. | 403. | CHT-E3, CHT-T03 | [ ] | |
| 103 | Putuskan koneksi lalu pulihkan. | Sistem mencoba menyambung ulang dan mengambil pesan yang terlewat. | CHT-E1, CHT-T07 | [ ] | |
| 104 | Uji tanpa Pusher. | Polling mengambil pesan baru dan fitur tetap berfungsi. | CHT-E7, CHT-T11 | [ ] | |
| 105 | Ambil job sebagai inspektor lalu buka chat job. | Percakapan job tersedia antara inspektor dan penjual. | CHT-R2, CHT-T13 | [ ] | |

## 12. Notifikasi (P1)

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 106 | Picu event yang menghasilkan notifikasi, misalnya job diambil atau jadwal dibuat. | Notifikasi tersimpan untuk penerima yang benar. | NTF-R2, NTF-T01 | [ ] | |
| 107 | Picu event yang sama dua kali. | Tidak ada notifikasi ganda. | NTF-E2, NTF-T02 | [ ] | |
| 108 | Buka notifikasi milik pengguna lain. | 403. | NTF-E4, NTF-T03 | [ ] | |
| 109 | Tandai satu notifikasi lalu semua sebagai dibaca. | Status belum dibaca berubah sesuai aksi. | NTF-R7, NTF-T04 | [ ] | |
| 110 | Sebagai pengguna diblokir, picu kejadian baru. | Pengguna tidak menerima notifikasi baru. | NTF-R6, NTF-T09 | [ ] | |
| 111 | Matikan real-time/Pusher. | Notifikasi tetap tersimpan dan dapat dimuat lewat polling. | NTF-E1, NTF-T06 | [ ] | |

## 13. Dashboard, pengaturan, dan admin

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 112 | Buka dashboard sebagai pembeli, penjual, inspektor, dan admin. | Kartu dan data sesuai role. | DSH-R1, DSH-T01, PRD 28.7 | [ ] | |
| 113 | Buka dashboard sebagai pengguna baru tanpa data. | Empty state dan ajakan tindakan yang jelas. | DSH-E1, DSH-T05 | [ ] | |
| 114 | Buka dashboard inspektor yang belum diverifikasi. | Diarahkan ke status verifikasi, bukan dashboard job kosong. | DSH-E2, DSH-T06 | [ ] | |
| 115 | Bandingkan angka pendapatan dashboard dengan halaman Pendapatan. | Angka berasal dari sumber ledger yang sama. | DSH-R2, DSH-R3, DSH-E3 | [ ] | |
| 116 | Ubah tarif/komisi/parameter waktu sebagai admin. | Nilai valid tersimpan; data baru memakai nilai baru. | ADM-R3, ADM-T05, ADM-T06 | [ ] | |
| 117 | Masukkan nilai pengaturan tidak valid. | Validasi gagal dan tidak tersimpan sebagian. | ADM-E2, ADM-T05 | [ ] | |
| 118 | Sebagai admin, pantau job dan lakukan aksi manual yang tersedia. | Aksi sesuai status dan memakai konfirmasi. | ADM-R2, ADM-T07, ADM-T08 | [ ] | |
| 119 | Buka kembali hasil terkunci sebagai admin. | Hasil menjadi draft dan inspektor dinotifikasi. | ADM-T09, RES-E14 | [ ] | |
| 120 | Lakukan aksi sensitif admin. | Aksi tercatat sesuai audit log yang tersedia. | ADM-R8, ADM-T13 | [ ] | |

## 14. Pengaturan akun dan keluar

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 121 | Ubah nama dan nomor HP dari pengaturan akun. | Data berubah setelah validasi. | AUTH-R11, AUTH-T13 | [ ] | |
| 122 | Coba ubah email. | Perubahan email ditolak sesuai status aturan saat ini atau menunjukkan bahwa verifikasi email masih ditunda. | AUTH-R11 | [ ] | |
| 123 | Ganti password tanpa password lama yang benar. | Perubahan ditolak. | AUTH-R11, AUTH-T13 | [ ] | |
| 124 | Ganti password dengan data benar. | Password berubah dan akun dapat masuk dengan password baru. | AUTH-T13 | [ ] | |
| 125 | Keluar dari akun. | Sesi berakhir dan halaman terlindungi meminta login kembali. | AUTH flow | [ ] | |

## 15. Uji khusus perangkat

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 126 | Ulangi alur utama pada Chrome Android. | Alur dapat selesai. | PRD 23.3, 28.8 | [ ] | |
| 127 | Ulangi alur utama pada Safari iPhone. | Alur dapat selesai. | PRD 23.3, 28.8 | [ ] | |
| 128 | Ulangi alur utama pada Chrome desktop. | Alur dapat selesai. | Issue #33 | [ ] | |
| 129 | Uji 360 px, 768 px, dan 1280 px. | Layout tetap usable dan tidak ada aksi utama yang terpotong. | UI-12 | [ ] | |
| 130 | Tolak izin kamera. | Ada petunjuk pemulihan dan foto wajib tetap tidak dapat dilewati. | RES-E2 | [ ] | |
| 131 | Coba foto dari galeri. | Ditolak. | RES-E5 | [ ] | |
| 132 | Uji foto >2 MB. | Kompresi/galat mengikuti RES-E1. | RES-E1 | [ ] | |
| 133 | Putar orientasi layar saat form. | Form dan tombol tetap dapat digunakan. | QA-P0 §3 | [ ] | |
| 134 | Buka keyboard HP saat mengisi form. | Field dan tombol penting tetap dapat diakses. | QA-P0 §3 | [ ] | |
| 135 | Klik tombol aksi dua kali cepat. | Tidak ada efek ganda pada pembayaran, pengiriman hasil, atau aksi lain yang idempoten. | PAY-E1, RES-E6, CHT-E6, TRX-E6 | [ ] | |
| 136 | Aktifkan throttling koneksi dan ulangi penyimpanan/kirim form. | Keadaan koneksi buruk ditangani; draf tidak hilang. | RES-E3, QA-P0 §3 | [ ] | |
| 137 | Pindah halaman sebelum draf tersimpan. | Draf tetap dapat dipulihkan. | RES-E3, RES-E4 | [ ] | |

## 16. Uji keamanan ringan

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 138 | Sebagai role A, buka alamat halaman khusus role B secara langsung. | 403 atau penolakan sesuai aturan. | AUTH-T12, DSH-E6 | [ ] | |
| 139 | Buka detail job milik inspektor lain. | Akses ditolak. | JOB-R5, otorisasi flow JOB | [ ] | |
| 140 | Sebagai pengguna lain, buka URL dokumen inspektor. | 403. | ONB-E3, ONB-T07 | [ ] | |
| 141 | Sebagai inspektor lain, buka draf hasil inspeksi. | 403. | RES-E11, RES-T14 | [ ] | |
| 142 | Ubah alamat URL foto hasil inspeksi ke foto milik objek lain. | Tautan tidak dapat dipakai untuk membuka foto pihak lain. | RES-R7, RES-T16 | [ ] | |
| 143 | Sebagai pengguna publik, periksa respons listing untuk data privat. | Tidak ada koordinat persis, alamat lengkap, nomor rangka, atau data privat lain. | CAT-T06, PRD 28.8 | [ ] | |
| 144 | Login dengan akun yang sudah diblokir. | Akses ditolak. | AUTH-E4, PRD 28.8 | [ ] | |
| 145 | Sebagai bukan peserta, baca atau kirim chat. | 403. | CHT-E3 | [ ] | |
| 146 | Sebagai pengguna lain, buka notifikasi milik orang lain. | 403. | NTF-E4 | [ ] | |

## 17. Performa dan aksesibilitas

| No | Langkah | Hasil yang diharapkan | Sumber | Lulus? | Catatan |
| --- | --- | --- | --- | :---: | --- |
| 147 | Buka halaman daftar barang pada Chrome desktop. | Halaman siap diaudit. | PRD 23.3 | [ ] | |
| 148 | Jalankan Lighthouse dengan mode Mobile pada daftar barang. | Performance minimal 80 dan Accessibility minimal 90. | UI-9, PRD 28.8 | [ ] | |
| 149 | Jalankan Lighthouse dengan mode Mobile pada detail listing. | Performance minimal 80 dan Accessibility minimal 90. | UI-9 | [ ] | |
| 150 | Periksa kontras teks dan kontrol. | Kontras memenuhi WCAG AA. | UI-9 | [ ] | |
| 151 | Navigasi dengan keyboard pada desktop. | Fokus terlihat dan urutan tab logis. | UI-9, UI-12 | [ ] | |
| 152 | Periksa label input dan nama aksesibel tombol/ikon. | Semua input memiliki label; ikon bermakna memiliki teks/aria-label. | UI-9 | [ ] | |
| 153 | Buka modal konfirmasi. | Fokus terjebak di modal dan Esc dapat menutupnya. | UI-9 | [ ] | |
| 154 | Picu galat atau notifikasi dinamis. | Perubahan penting diumumkan secara aksesibel. | UI-9 | [ ] | |

Untuk Lighthouse, jalankan audit dari Chrome DevTools pada halaman target dan simpan skor/hasil audit sebagai bagian dari bukti pengujian. Dokumentasi Chrome DevTools menjelaskan Lighthouse sebagai alat audit performa dan kualitas halaman. urlChrome DevToolshttps://developer.chrome.com/docs/devtools/

## 18. Format laporan temuan

Gunakan satu baris untuk setiap temuan yang dapat direproduksi.

| No | Halaman/rute | Langkah | Yang terjadi | Yang diharapkan | Tingkat (Blokir/Mayor/Minor) | Tangkapan layar |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | | | | | | |
| 2 | | | | | | |
| 3 | | | | | | |
| 4 | | | | | | |
| 5 | | | | | | |

## 19. Batasan yang diketahui

Jangan melaporkan hal berikut sebagai bug hanya karena berbeda dari sistem pembayaran/komunikasi produksi:

- Pembayaran SIMOKAS adalah **simulasi**.
- Jika Pusher belum dikonfigurasi, real-time dapat memakai **polling** sebagai cadangan.
- Fitur yang ditandai **P1** bukan bagian dari alur P0 dan hanya diuji bila implementasinya sudah tersedia.
- Jika sebuah perilaku belum cukup jelas di sumber, jangan menebak. Tulis `[perlu dikonfirmasi]` dan masukkan ke daftar konfirmasi PR.

## 20. Daftar konfirmasi sebelum QA-P0 dimulai

| No | Hal yang perlu dikonfirmasi | Mengapa |
| --- | --- | --- |
| 1 | Alamat aplikasi yang menjadi target pengujian. | Tidak ada alamat aplikasi pada dokumen sumber yang dibaca. |
| 2 | Akun demo untuk tiap role. | `docs/DEMO.md` belum tersedia di repo. |
| 3 | Nilai dan cara pengujian data demo yang belum tersedia pada environment. | PRD hanya menentukan bentuk data demo, bukan kredensial aktual. |
| 4 | Bagian aturan yang masih berstatus [OPEN]/[USULAN] jika hasil manual berbeda. | Penguji tidak boleh mengambil keputusan bisnis sendiri. |
