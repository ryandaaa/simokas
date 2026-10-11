# Flow: Listing (LST)

Prioritas P0. Referensi PRD: 12.2, 12.8, 13.3, 14.1, 16, 18.4–18.6. Keputusan: D-05 (edit), D-02 (beli saat inspeksi).

## Aktor

Penjual (pemilik), admin.

## 1. State machine

```mermaid
stateDiagram-v2
  [*] --> pending_review : penjual kirim
  pending_review --> active : admin setujui
  pending_review --> rejected : admin tolak (alasan)
  rejected --> pending_review : penjual perbaiki dan kirim ulang
  active --> pending_review : edit field inti (D-05)
  active --> in_inspection : job dibayar
  in_inspection --> active : job selesai, batal, atau kedaluwarsa
  active --> in_transaction : pembayaran beli sukses (P1)
  in_transaction --> active : transaksi dibatalkan
  in_transaction --> sold : serah terima dikonfirmasi
  active --> inactive : penjual atau admin nonaktifkan
  inactive --> active : penjual aktifkan kembali
  sold --> [*]
```

Catatan: pembayaran beli tidak dapat terjadi saat `in_inspection` (D-02). Pembeli bisa mengajukan dan penjual menyetujui, tetapi pembayaran menunggu job aktif selesai.

## 2. Membuat listing

```mermaid
flowchart TD
  A["Tambah Barang"] --> B["Data kendaraan: jenis, merek, model, tahun, harga, kilometer, dst."]
  B --> C["Unggah 3-10 foto, kompres di klien"]
  C --> D["Pilih titik di peta + alamat lengkap"]
  D --> P{"Titik di dalam Pekanbaru?"}
  P -- "Tidak" --> D
  P -- "Ya" --> E["Isi opsi waktu inspeksi (min 1) + tempat inspeksi"]
  E --> V{"Validasi server"}
  V -- "Gagal" --> B
  V -- "Lolos" --> S["Status pending_review"]
  S --> AD{"Admin"}
  AD -- "Setujui" --> ACT["active, tampil publik"]
  AD -- "Tolak + alasan" --> REJ["rejected, notifikasi penjual"]
  REJ --> B
```

## 3. Mengedit listing aktif (D-05)

```mermaid
flowchart TD
  E["Penjual mengedit"] --> L{"Ada job aktif atau transaksi?"}
  L -- "Ya" --> LK["Field inti terkunci. Field non-inti boleh, kecuali saat in_transaction (semua terkunci)"]
  L -- "Tidak" --> C{"Yang diubah"}
  C -- "Non-inti: harga, nego, deskripsi, foto, warna, slot dan tempat inspeksi" --> N["Berlaku langsung"]
  C -- "Inti: jenis, merek, model, tahun, nomor rangka, kilometer" --> I["Status kembali pending_review, lencana dicabut"]
  I --> AD["Admin validasi ulang"]
```

## 4. Aturan bisnis

| ID | Aturan |
| --- | --- |
| LST-R1 | Listing baru `pending_review`, tidak tampil publik |
| LST-R2 | Foto 3–10, maks 2 MB per foto, JPG/PNG/WebP. Satu foto ditandai sampul |
| LST-R3 | Titik lokasi harus di dalam wilayah Pekanbaru, divalidasi di server. Batas memakai bounding box konservatif [USULAN] di `PekanbaruBoundary` (lat 0,40–0,63; lng 101,28–101,76) sampai data batas resmi tersedia (lihat `docs/decisions/0005-leaflet-map.md`) |
| LST-R4 | Lokasi persis, alamat lengkap, nomor rangka, dan tempat inspeksi (`inspection_place`) tidak pernah di respons publik. `inspection_place` bersifat privat (dapat berisi alamat rumah penjual, PRD 13.3 dan 19) dan hanya tampil untuk pemilik dan admin; publik hanya memakai `area_name`. Titik perkiraan publik (`ApproximateLocation`, pembulatan 2 desimal, maks 800 m) disiapkan untuk peta publik CAT-2 |
| LST-R5 | Opsi waktu inspeksi minimal 1 dan tidak di masa lalu |
| LST-R6 | Penjual tidak dapat menghapus atau menonaktifkan listing yang punya job aktif atau transaksi |
| LST-R7 | Edit mengikuti D-05. Field inti terkunci saat ada job aktif atau transaksi |
| LST-R8 | Listing `sold` tidak dapat diubah |
| LST-R9 | Pengguna yang diblokir: listing aktifnya otomatis `inactive` [USULAN] |
| LST-R10 | Perubahan status hanya lewat Action domain Listing, dipicu event dari domain lain |
| LST-R11 | Foto listing berstatus photo-public (`active`, `in_inspection`, `in_transaction`, `sold` [USULAN]) dapat dibuka siapa pun termasuk tamu dan pengguna dengan status akun apa pun (aktif, pending, blocked), dengan header cache publik. `sold` termasuk agar halaman Terjual tetap menampilkan galeri [USULAN]. Foto `pending_review`, `rejected`, `inactive` hanya untuk pemilik aktif dan admin aktif (header cache privat); tamu, pengguna blocked/non-aktif, dan pemilik pending ditolak. Sumber kebenaran: `ListingStatus::isPhotoPubliclyVisible()` |

## 5. Edge case

| ID | Situasi | Perilaku |
| --- | --- | --- |
| LST-E1 | Pin di luar Pekanbaru | Ditolak, pesan jelas |
| LST-E2 | Foto > 2 MB atau bukan gambar | Dikompresi di klien, bila tetap gagal pesan jelas |
| LST-E3 | Foto kurang dari 3 atau lebih dari 10 | Validasi gagal |
| LST-E4 | Menonaktifkan atau menghapus listing saat ada job aktif | Ditolak, penjual diminta membatalkan job dulu |
| LST-E5 | Edit field inti setelah diinspeksi | Lencana dicabut, `pending_review` |
| LST-E6 | Penjual mengubah harga saat ada penawaran beli berjalan | Penawaran memakai harga yang disalin saat diajukan, penjual diberi peringatan |
| LST-E7 | Listing ditolak, penjual mengirim ulang tanpa perubahan | Diperbolehkan, kembali ke antrean |
| LST-E8 | Dua admin memvalidasi listing yang sama | Aksi kedua gagal karena status sudah berubah |
| LST-E9 | Pengguna lain mengedit listing | 403 |
| LST-E10 | Akses `GET /listings/{id}` untuk listing non-publik oleh bukan pemilik/admin | 404 |
| LST-E11 | Listing `sold` dibuka | Tampil sebagai Terjual, tanpa tombol aksi |
| LST-E12 | Lencana dicabut saat ada pembeli yang sudah membayar akses | Riwayat pembayar tetap menampilkan hasil lama |

## 6. Daftar test case

- [x] `LST-T01` Penjual membuat listing valid, status `pending_review`, tidak tampil di daftar publik
- [x] `LST-T02` Validasi field (dataset): tahun, harga, kilometer, judul, foto, slot
- [x] `LST-T03` Titik di luar Pekanbaru ditolak (LST-E1)
- [x] `LST-T04` Admin menyetujui: `active`, menolak dengan alasan: `rejected`
- [x] `LST-T05` Penjual memperbaiki dan mengirim ulang listing ditolak (LST-E7)
- [x] `LST-T06` Edit non-inti berlaku langsung tanpa mengubah status
- [x] `LST-T07` Edit inti mengembalikan ke `pending_review` dan mencabut lencana (LST-E5)
- [x] `LST-T08` Field inti terkunci saat ada job aktif, semua terkunci saat `in_transaction`
- [x] `LST-T09` Nonaktifkan ditolak saat ada job aktif atau transaksi (LST-E4)
- [x] `LST-T10` Transisi status: pasangan sah diterima, selain itu ditolak (matriks penuh 7×7)
- [x] `LST-T11` Respons publik tidak memuat `lat`, `lng`, `address_full`, `chassis_number` (LST-R4)
- [x] `LST-T12` Pengguna lain tidak dapat mengedit (LST-E9), dataset role
- [x] `LST-T13` Listing non-publik tidak dapat dibuka orang lain (LST-E10)
- [x] `LST-T14` Event `JobCompleted` mengisi `is_inspected` dan `last_inspected_at` (menyusul di JOB-4; Action `MarkListingInspected` sudah siap, lihat `docs/features/listing-contracts.md`)
- [x] `LST-T15` Event `UserBlocked` menonaktifkan listing aktif pengguna (LST-R9) (ADM-4)
- [x] `LST-T16` Aksi admin ganda hanya berlaku sekali (LST-E8)
- [x] `LST-T17` Foto status photo-public (`active`, `in_inspection`, `in_transaction`, `sold` [USULAN]) dapat dibuka semua penonton (tamu, pemilik aktif/pending, admin, pengguna lain aktif, inspektor pending/approved, blocked, non-aktif) dengan header cache publik; non-publik hanya pemilik aktif/admin aktif dengan cache privat (LST-R11; dataset semua status × peran). `ListingPolicy::view` tidak diubah: halaman detail publik non-`active` tetap ditangani aturan visibilitas milik domain Catalog
- [x] `LST-T18` Pratinjau foto baru di form buat iklan memakai URL lokal browser, bukan endpoint preview bertanda tangan
- [x] `LST-T19` Entry peta tersedia sebelum langkah lokasi dimasukkan Livewire; picker tetap menampilkan atribusi OSM dan fallback koordinat manual
- [x] `LST-T20` Pratinjau foto baru saat edit iklan tidak meminta URL preview bertanda tangan
- [x] `LST-T21` Inisialisasi peta pada event browser tidak memperlakukan objek event sebagai root DOM
- [x] `LST-T22` Asset Vite peta tidak menggantikan root komponen Livewire pada render komponen
- [x] `LST-T23` Root Livewire pada respons halaman buat iklan tetap elemen `<div>`
- [x] `LST-T24` Root Livewire pada respons halaman edit iklan tetap elemen `<div>`
