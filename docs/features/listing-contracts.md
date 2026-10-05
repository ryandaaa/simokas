# Kontrak Listing untuk Domain Lain

Dokumen ringkas untuk tim **JOB-1** (Inspection) dan **TRX-1** (Transaction).
Aturan edit dan state machine: `docs/flows/listing.md` (LST-R6–R10, D-05).

## 1. `Listing\Contracts\ListingActivityChecker`

```php
interface ListingActivityChecker
{
    public function hasActiveJob(string $listingId): bool;
    public function hasActiveTransaction(string $listingId): bool;
}
```

- Binding bawaan: `Listing\Services\DefaultListingActivityChecker` (selalu `false`).
- Dipakai `UpdateListing` (kunci field inti, LST-R7) dan `DeactivateListing`
  (tolak nonaktifkan saat ada job/transaksi, LST-R6/LST-E4).
- **JOB-1** mengganti binding agar `hasActiveJob()` membaca `inspection_jobs`
  (job bukan status akhir). **TRX-1** melakukan hal yang sama untuk
  `hasActiveTransaction()`. Kode Listing tidak berubah.

## 2. Action yang dipanggil listener milik JOB (wiring di kartu JOB)

| Action | Efek | Idempoten |
| --- | --- | --- |
| `MarkListingInInspection` | `active` → `in_inspection` | Ya (sudah `in_inspection` = sukses tanpa efek) |
| `ReturnListingToActive` | `in_inspection` → `active` | Ya (sudah `active` = sukses tanpa efek) |
| `MarkListingInspected` | `is_inspected = true`, isi `last_inspected_at` | Ya (dipakai **LST-T14** di JOB-4) |

Semua memakai pembaruan berkondisi: bila status sudah berubah, gagal tanpa
efek (`InvalidListingStatusTransitionException`). Jangan membuat listener di
domain Listing; listener milik domain pemancar event.

## 3. Catatan untuk TRX-1

- Harga penawaran **disalin** saat diajukan. Perubahan harga listing tidak
  memengaruhi penawaran berjalan; form edit menampilkan peringatan ini
  (LST-E6).
- Pembayaran beli menunggu job aktif selesai (D-02); listing tetap satu nilai
  status (`in_inspection` dulu, `in_transaction` setelahnya).

## 4. Yang menyusul di kartu lain

- **LST-T14** (`JobCompleted` mengisi lencana): JOB-4 memanggil
  `MarkListingInspected` lalu `ReturnListingToActive`.
- **LST-T15** (`UserBlocked` menonaktifkan listing): ADM-4, via
  `DeactivateListing` per listing aktif pengguna.
- **LST-E12** (lencana dicabut saat pembeli sudah membayar akses): riwayat
  pembayar tetap menampilkan hasil lama — tanggung jawab domain Result.
- Tampilan publik (`Terjual` tanpa tombol aksi, LST-E11) dan daftar publik
  dikerjakan di **CAT-2** memakai `ListingPolicy::view` dan
  `ListingPublicResource` yang sudah siap.
- Foto publik: `ListingPolicy::viewPhoto` dan `ViewListingPhotoController`
  memakai `ListingStatus::isPhotoPubliclyVisible()` (`active`,
  `in_inspection`, `in_transaction`, `sold` [USULAN]; LST-R11), sehingga
  galeri di etalase dan halaman detail publik tidak 403 untuk siapa pun
  (tamu maupun login dengan status akun apa pun). Foto non-publik
  (`pending_review`, `rejected`, `inactive`) tetap hanya untuk pemilik aktif
  dan admin aktif.
