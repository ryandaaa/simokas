# Backlog Fitur

Sumber daftar kartu untuk papan kanban GitHub. Satu baris = satu issue. Cara mengerjakan kartu: [AGENT_TASK.md](AGENT_TASK.md).

## Cara membaca

| Kolom | Arti |
| --- | --- |
| **ID** | ID kartu, dipakai di judul issue (`[JOB-2] Daftar job terbuka dan Ambil Job atomik`). Angka dalam kurung adalah nomor issue GitHub |
| **Flow & test** | Flow doc dan ID test case yang **wajib lulus** saat kartu selesai |
| **Butuh** | Kartu yang harus selesai (di-merge) lebih dulu |
| **Uk.** | Perkiraan ukuran: S (< 0,5 hari), M (~ 1 hari), L (1–2 hari) |

Test case ID merujuk ke bagian "Daftar test case" di `flows/<modul>.md`. Test case yang bergantung pada kartu lain ditulis di kartu yang membuatnya mungkin.

## Papan dan label

| Kolom | Arti |
| --- | --- |
| Backlog | Belum siap dikerjakan (prasyarat belum selesai atau spesifikasi belum jelas) |
| Ready | Prasyarat selesai, spesifikasi jelas, boleh diambil |
| Ongoing | Sedang dikerjakan. Satu orang per kartu, cantumkan assignee |
| Review | PR terbuka, menunggu review dan CI hijau |
| Done | Sudah di-merge ke `main` |

Label: `P0`, `P1`, `P2`, `domain:<nama>` (identity, inspector, listing, catalog, inspection, result, payment, transaction, chat, rating, notification, setting, admin, dashboard, foundation), `type:feature|infra|docs|test`, `blocked`, `shared` (menyentuh kontrak, event, Support, atau komponen UI bersama), `open-question`.

Milestone: **P0 – Deploy 8 Okt**, **P1 – Pengumpulan 12 Okt**, **P2 – Setelah lomba**.

## Urutan dan jalur paralel

1. **F1 selesai dulu.** Semua kartu lain bergantung ke sana. Selama itu anggota lain mengerjakan F4 (verifikasi hosting) dan wireframe.
2. Setelah F1, dua sampai tiga orang dapat berjalan paralel pada jalur yang tidak saling menyentuh:

| Jalur | Isi | Kartu |
| --- | --- | --- |
| A. Pengguna dan listing | Akun, listing, katalog | F2, AUTH-*, ONB-*, LST-*, CAT-* |
| B. Uang dan job | Pengaturan, pembayaran, job | F3, ADM-1, PAY-*, JOB-* |
| C. Hasil dan penutup | Hasil inspeksi, dashboard | RES-*, DSH-1, ADM-2, J1, DEMO |

3. Titik temu antar-jalur: **JOB-1 butuh PAY-1** (kontrak `Billing`), **RES-1 butuh JOB-3**, **RES-4 butuh PAY-1 dan PAY-2**, **CAT-2 butuh RES-4** untuk lencana. Bila belum siap, definisikan kontraknya dulu dalam PR kecil berlabel `shared`.

## Fondasi

| ID | Judul | Domain | Flow & test | Butuh | Uk. |
| --- | --- | --- | --- | --- | :-: |
| F1 (#1) | Kerangka Laravel 12, Pest, Pint, Larastan, CI, skeleton domain + ServiceProvider, arch test, skrip `composer check`. Validasi seluruh contoh di TESTING.md | foundation | ARCHITECTURE, TESTING. CI hijau, arch test hijau | - | L |
| F2 (#2) | UI kit: token Tailwind, layout (bilah atas, menu bawah HP), semua komponen `x-ui.*`, halaman 403/404/500, `lang/id` | foundation | UI_GUIDELINES. Test render setiap komponen | F1 | L |
| F3 (#3) | Support: `Money` (split, format), `Clock` (UTC/WIB), handler exception domain, kelas dasar exception | foundation | CONVENTIONS 4, 5, 7. `PAY-T02` | F1 | S |
| F4 (#4) | Verifikasi dan deploy Hostinger: PHP 8.3, SSH/Composer, cron `schedule:run`, versi MySQL (kolom virtual berindeks unik), koneksi Pusher. Panduan deploy dan daftar `.env` | foundation | decisions/0001 bagian "yang harus diverifikasi". Dokumen `docs/DEPLOY.md` | - | M |

## P0. Identity dan Inspector

| ID | Judul | Domain | Flow & test | Butuh | Uk. |
| --- | --- | --- | --- | --- | :-: |
| AUTH-1 (#5) | Registrasi pengguna, login, logout, lupa dan reset password, seeder admin | identity | auth. `AUTH-T01–T08`, `T10`, `T14` | F1, F2 | L |
| AUTH-2 (#6) | Guard per role, redirect dashboard, halaman akun diblokir | identity | auth. `AUTH-T09`, `T11`, `T12` | AUTH-1 | M |
| AUTH-3 (#7) | Profil: ubah nama dan HP, ganti password | identity | auth. `AUTH-T13` | AUTH-1 | S |
| ONB-1 (#8) | Daftar inspektor dan unggah dokumen (disk privat, tautan sementara) | inspector | onboarding. `ONB-T01–T03`, `T07` | AUTH-1 | M |
| ONB-2 (#9) | Antrean verifikasi inspektor oleh admin, status verifikasi, inspektor `pending` terkunci | inspector, admin | onboarding. `ONB-T04–T06`, `T08–T11` | ONB-1, AUTH-2 | M |

## P0. Listing dan Catalog

| ID | Judul | Domain | Flow & test | Butuh | Uk. |
| --- | --- | --- | --- | --- | :-: |
| LST-1 (#10) | Form listing: data kendaraan, 3–10 foto (kompres di klien), slot dan tempat inspeksi, status `pending_review` | listing | listing. `LST-T01`, `T02`, `T12` | AUTH-2, F2 | L |
| LST-2 (#11) | Peta Leaflet, pin lokasi, validasi dalam Pekanbaru, privasi koordinat dan alamat | listing | listing. `LST-T03`, `T11` | LST-1 | M |
| LST-3 (#12) | Validasi listing oleh admin (setujui, tolak + alasan), kirim ulang yang ditolak | listing, admin | listing. `LST-T04`, `T05`, `T16` | LST-1, AUTH-2 | M |
| LST-4 (#13) | Edit listing (aturan D-05), nonaktifkan, state machine listing, listener event job | listing | listing. `LST-T06–T10`, `T13` | LST-3 | M |
| CAT-1 (#14) | Daftar barang: kartu, pencarian, filter, urutan, pagination 12, empty state | catalog | catalog. `CAT-T01–T05`, `T10`, `T11` | LST-3 | L |
| CAT-2 (#15) | Detail listing: galeri, visibilitas data per peran, matriks tombol aksi, peta perkiraan | catalog | catalog. `CAT-T06–T09`. `CAT-T12` setelah RES-4 | CAT-1, LST-2 | M |
| CAT-3 (#16) | Beranda, Cara Kerja, FAQ | catalog | catalog 1. Feature test render | F2 | S |

## P0. Setting dan Payment

| ID | Judul | Domain | Flow & test | Butuh | Uk. |
| --- | --- | --- | --- | --- | :-: |
| ADM-1 (#17) | Pengaturan tarif, komisi, `auto_accept_days`, `paid_job_timeout_days`: tabel, seeder default, UI admin | setting, admin | admin. `ADM-T01`, `T05` | AUTH-2 | M |
| PAY-1 (#18) | Tagihan, halaman bayar **SIMULASI**, hasil berhasil/gagal/tertunda, idempotency key, kedaluwarsa, kontrak `Billing` | payment | payment. `PAY-T01`, `T03–T07`, `T14` | ADM-1, F3 | L |
| PAY-2 (#19) | Ledger, posting bagi hasil, refund idempoten, saldo dan halaman Pendapatan inspektor | payment | payment. `PAY-T08–T10`, `T12`, `T13`, `T15` | PAY-1 | L |

## P0. Inspection (job)

| ID | Judul | Domain | Flow & test | Butuh | Uk. |
| --- | --- | --- | --- | --- | :-: |
| JOB-1 (#20) | Ajukan inspeksi: job `requested`, tarif tersalin, kolom virtual + indeks unik, listing `in_inspection` setelah dibayar | inspection | job. `JOB-T01`, `T02`, `T04`, `T16`, `T17`. `ADM-T06`, `PAY-T11` | PAY-1, LST-4 | L |
| JOB-2 (#21) | Daftar job terbuka (filter), Ambil Job atomik, batas 3 job, larangan listing sendiri, alamat persis setelah diambil | inspection | job. `JOB-T03`, `T05`, `T06` | JOB-1, ONB-2 | L |
| JOB-3 (#22) | Jadwal, mulai inspeksi, pembatalan oleh inspektor dan pemohon, refund, state machine lengkap | inspection | job. `JOB-T07`, `T08`, `T13`, `T14` | JOB-2, PAY-2 | L |
| JOB-4 (#23) | Kirim ke Terima Hasil, job `completed`, fee cair, lencana ke listing, aksi admin manual (selesaikan, batalkan) | inspection, admin | job. `JOB-T15`, `T18`, `T19`. `ADM-T07`, `T08`. `LST-T14` | JOB-3, RES-3 | M |
| JOB-5 (#24) | Tugas terjadwal: tagihan 30 menit, job `paid` 7 hari, auto-selesai 3 hari, tandai perlu tindakan 48 jam | inspection | job. `JOB-T09–T12` | JOB-3, F4 | M |

## P0. Result (hasil inspeksi)

| ID | Judul | Domain | Flow & test | Butuh | Uk. |
| --- | --- | --- | --- | --- | :-: |
| RES-1 (#25) | Template inspeksi (tabel, versi, seeder sementara mobil dan motor), form per kategori, simpan draf | result | result. `RES-T01`, `T02`, `T14`, `T15` | JOB-3 | L |
| RES-2 (#26) | Komponen kamera, foto bercap waktu, tolak foto non-kamera, disk privat | result | result. `RES-T04`, `T05`, `T16` | RES-1 | L |
| RES-3 (#27) | Kirim hasil: validasi item wajib dan foto minimal, hitung skor dan rekomendasi, item kritis, kunci laporan | result | result. `RES-T03`, `T06–T09` | RES-2 | M |
| RES-4 (#28) | Tampilan hasil, akses terkunci, bayar akses, `result_access`, penutupan akses saat `sold` | result | result. `RES-T10–T13`, `T17` | RES-3, PAY-2 | L |

## P0. Penutup

| ID | Judul | Domain | Flow & test | Butuh | Uk. |
| --- | --- | --- | --- | --- | :-: |
| DSH-1 (#29) | Dashboard pembeli, penjual, inspektor, admin | dashboard | dashboard. `DSH-T01–T09` | JOB-4, PAY-2 | L |
| ADM-2 (#30) | Antrean admin terpadu, pantau job dan transaksi, buka kembali hasil, pencatatan aksi sensitif | admin | admin. `ADM-T02–T04`, `T09`, `T13` | JOB-4, ONB-2 | M |
| J1 (#31) | Journey test end-to-end: listing → inspeksi → hasil → akses berbayar | foundation | TESTING 7. `J1`, `J2` | RES-4, DSH-1 | M |
| DEMO (#32) | DemoSeeder (3 pengguna, 3 inspektor, 15–20 listing, 4–5 hasil, job terbuka), akun demo, skrip demo 5 menit | foundation | PRD 23.4 | RES-4 | M |
| QA-P0 (#33) | Uji asap di Chrome Android, Safari iPhone, Chrome desktop, Lighthouse daftar dan detail, perbaikan galat | foundation | PRD 28.8 | semua P0 | M |

## P1

| ID | Judul | Domain | Flow & test | Butuh | Uk. |
| --- | --- | --- | --- | --- | :-: |
| TRX-1 (#34) | Ajukan pembelian, rincian potongan penjual, setuju atau tolak | transaction | transaction. `TRX-T01–T06` | JOB-4 | L |
| TRX-2 (#35) | Pembayaran (ditunda saat ada job aktif), dana ditahan, serah terima, posting ledger, listing `sold` | transaction | transaction. `TRX-T07–T09`, `T15`, `T16` | TRX-1, PAY-2 | L |
| TRX-3 (#36) | Pembatalan (aturan D-04), kedaluwarsa penawaran, tandai perlu tindakan | transaction | transaction. `TRX-T10–T14` | TRX-2 | M |
| CHT-1 (#37) | Percakapan, kirim pesan, belum dibaca, polling cadangan, percakapan job saat `JobTaken` | chat | chat. `CHT-T01–T09`, `T11`, `T13` | JOB-2 | L |
| CHT-2 (#38) | Real-time Laravel Echo + Pusher, otorisasi kanal privat, sambung ulang | chat | chat. `CHT-T10`, `T12`. Verifikasi `CHT-T02`, `T03` | CHT-1, F4 | M |
| RTG-1 (#39) | Rating inspektor setelah transaksi selesai, rata-rata, sembunyikan oleh admin | rating | rating. `RTG-T01–T08` | TRX-2 | M |
| NTF-1 (#40) | Notifikasi tersimpan, lonceng, tandai dibaca, listener untuk semua pemicu | notification | notification. `NTF-T01–T05`, `T07–T10` | JOB-4 | L |
| NTF-2 (#41) | Notifikasi real-time lewat Pusher, polling cadangan | notification | notification. `NTF-T06` | NTF-1, CHT-2 | S |
| ADM-3 (#42) | Pengelolaan template inspeksi lewat UI, versi | admin, result | admin. `ADM-T12` | RES-1 | M |
| ADM-4 (#43) | Blokir dan aktifkan pengguna, sembunyikan rating, aturan item aktif | admin, identity | admin. `ADM-T10`, `T11`. `AUTH-T09`. `LST-T15` | ADM-2, TRX-3 | M |
| LEGAL-1 (#44) | Kebijakan privasi, syarat ketentuan, persetujuan saat registrasi | identity | auth AUTH-R9 | AUTH-1 | S |
| HIST-1 (#45) | Riwayat transaksi dan hasil inspeksi lengkap | dashboard | PRD 7.1 (riwayat) | TRX-2, RES-4 | M |
| POLISH (#46) | Pemolesan UI, performa, uji akhir | foundation | UI_GUIDELINES 12 | semua P1 | M |
| DOC-1 (#47) | Sinkronkan PRD ke v1.6 (keputusan D-01…D-06) dan panduan inspektor satu halaman | docs | decisions/0002 "Dampak ke PRD" | - | S |

## P2 (setelah lomba)

| ID | Judul |
| --- | --- |
| P2-DARK | Mode gelap (set token kedua) |
| P2-AUDIT | Audit log dan analitik pemakaian |
| P2-DISPUTE | Pelaporan dan sanggahan hasil inspeksi |
| P2-RATELIMIT | Batasan job berdasarkan rating inspektor |
| P2-WITHDRAW | Tarik saldo (simulasi) |
| P2-RATING-REQ | Rating oleh pemohon inspeksi (RTG-R9) [OPEN] |
| P2-LOGOUT-ALL | Logout dari semua perangkat |
| P2-EMAIL | Notifikasi email |
| P2-EXPORT | Ekspor laporan |

## Pertanyaan terbuka yang memengaruhi kartu

| Kartu | Pertanyaan | Default sementara |
| --- | --- | --- |
| AUTH-1, TRX-1 | D-06: boleh inspektor membeli, menjual, atau mengajukan inspeksi? | Dilarang |
| RES-4, CAT-2 | Lencana publik menampilkan skor ringkas? (Q11) | Hanya tulisan "Sudah Diinspeksi" dan tanggal |
| TRX-1, TRX-2 | Siapa menanggung komisi 3% dan apakah platform memotong 10% darinya? (Q5) | Dipotong dari penjual, platform 10% atas komisi |
| RES-1 (#25) | Indikator inspeksi final dari tim | Template sementara (PRD 15.2) |
| LST-1 (#10) | Daftar merek dan model: dropdown atau bebas? (Q19) | Input bebas dengan saran |
| AUTH-1 (#5) | Verifikasi email di P0? | Dimatikan |
