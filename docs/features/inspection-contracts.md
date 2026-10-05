# Kontrak dan Integrasi Domain Inspection

Dokumen ini menjelaskan implementasi kontrak dan integrasi lintas domain untuk **JOB-1** (Inspection).

## 1. Penggantian Implementasi `ListingActivityChecker`

Kontrak `App\Domains\Listing\Contracts\ListingActivityChecker` digunakan oleh domain Listing untuk memeriksa apakah listing sedang terlibat aktivitas aktif (misalnya inspeksi atau transaksi penawaran), sehingga aksi pengubahan dan penonaktifan listing dapat dikunci (LST-R6, LST-R7, LST-E4).

### Implementasi: `InspectionListingActivityChecker`

- **Lokasi**: `App\Domains\Inspection\Services\InspectionListingActivityChecker`
- **`hasActiveJob(string $listingId): bool`**:
  Memeriksa apakah terdapat baris di tabel `inspection_jobs` untuk listing tersebut dengan status aktif (bukan status akhir: `completed`, `cancelled`, `expired`).
- **`hasActiveTransaction(string $listingId): bool`**:
  Didelegasikan ke fallback bawaan (`DefaultListingActivityChecker`) sehingga perilakunya tetap utuh (mengembalikan `false`) sampai domain Transaction menggantikannya pada tahap **TRX-1**.

### Strategi Pemenangan Binding Kontainer

Agar binding Inspection selalu menang atas binding bawaan Listing terlepas dari urutan pendaftaran provider di `bootstrap/providers.php`:

1. `ListingServiceProvider` mendaftarkan `DefaultListingActivityChecker` di method `register()`.
2. `InspectionServiceProvider` mendaftarkan `InspectionListingActivityChecker` di method `register()` **dan melakukan rebind di method `boot()`**.
3. Di siklus hidup Laravel (*application lifecycle*), seluruh pemanggilan `register()` pada semua service provider dieksekusi terlebih dahulu sampai tuntas, baru kemudian seluruh pemanggilan `boot()` dieksekusi.
4. Dengan melakukan rebind pada method `boot()`, binding milik `InspectionServiceProvider` dijamin dieksekusi setelah tahap `register()` milik `ListingServiceProvider`, sehingga binding Inspection selalu menjadi binding aktif di IoC container terlepas dari urutan array provider.

## 2. Reaksi Pembayaran dan Integrasi Shared

Mengikuti aturan batas domain (A2/A3 di `docs/ARCHITECTURE.md`):

1. **Inspection mendengarkan Pembayaran**:
   Listener `App\Domains\Inspection\Listeners\MarkJobPaidOnPaymentSucceeded` mendengarkan `PaymentSucceeded` dari domain Payment. Jika tujuan pembayaran adalah inspeksi (`PaymentPurpose::Inspection`), status job diperbarui dari `requested` ke `paid` secara idempoten (kondisi `where status = 'requested'`) dan mencatat `available_since`.
2. **Pancaran Event Publik**:
   Setelah job berstatus `paid`, Inspection memancarkan event publik `App\Domains\Inspection\Events\JobPaid` (hanya membawa ID primitif: `jobId`, `listingId`, `requesterId`, tanpa model Eloquent).
3. **Reaksi Domain Listing (Shared)**:
   Domain Listing mendaftarkan listener `App\Domains\Listing\Listeners\MarkListingInInspectionOnJobPaid` yang mendengarkan `JobPaid`. Listener ini memanggil Action internal milik Listing sendiri yaitu `App\Domains\Listing\Actions\MarkListingInInspection` untuk memindahkan status listing dari `active` ke `in_inspection`. Domain Inspection **tidak pernah** memanggil Action milik Listing secara langsung.
