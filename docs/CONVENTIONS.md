# Konvensi Kode

## 1. Bahasa

| Hal | Bahasa |
| --- | --- |
| Dokumen (`.md`) dan teks yang dilihat pengguna (UI, pesan validasi, notifikasi) | Indonesia |
| Class, method, variabel, tabel, kolom, rute, nama event, kode error | Inggris |
| Pesan commit, judul PR, nama branch | Inggris |
| Komentar kode | Inggris, hanya bila menjelaskan *mengapa*, bukan *apa* |

Teks UI disimpan di `lang/id/` (file terjemahan), bukan ditulis langsung di Blade atau PHP, supaya mudah diubah dan diuji.

## 2. Penamaan

| Jenis | Aturan | Contoh |
| --- | --- | --- |
| Action | Kata kerja + objek, `final class`, satu method `handle()` | `RequestInspection`, `TakeJob`, `SubmitResult` |
| Policy | `<Model>Policy` | `ListingPolicy` |
| Event | Kala lampau | `JobTaken`, `PaymentSucceeded` |
| Listener | Kata kerja yang dilakukan | `GrantResultAccess` |
| Enum | Singular, kasus PascalCase, nilai `snake_case` | `JobStatus::ResultSubmitted = 'result_submitted'` |
| Livewire | Deskriptif per layar | `Listing\Livewire\ListingForm` |
| Tabel | Jamak, `snake_case` | `inspection_jobs` |
| Kolom boolean | Awalan `is_` atau `has_` | `is_inspected` |
| Kolom waktu | Akhiran `_at` | `taken_at` |
| Rute | `kebab-case`, nama rute `domain.aksi` | `jobs.take` |
| Test | Memuat ID flow | `it('JOB-T03 only one inspector can take a job')` |

## 3. Database

- Primary key `uuid` (`HasUuids`). Foreign key `foreignUuid(...)->constrained()`.
- Waktu disimpan UTC (`timestamp`). Tampilan WIB lewat `Support\Clock`.
- Uang disimpan sebagai `unsignedBigInteger` rupiah bulat. **Tidak ada float** untuk uang.
- Enum disimpan sebagai `string` dengan `CHECK` bila didukung, dan di-cast ke Enum PHP di model.
- Array disimpan sebagai `json` dan di-cast.
- Aturan "satu job aktif per listing": kolom virtual `active_listing_id` + indeks unik (lihat PRD 18.7). Ditulis di migrasi domain Inspection dan wajib ada test.
- Migrasi **maju saja**. Migrasi yang sudah di-merge ke `main` tidak diedit. Perubahan skema dibuat sebagai migrasi baru.
- Migrasi, factory, dan seeder tinggal di folder domain pemiliknya.
- Setiap tabel yang memuat data pengguna punya indeks pada kolom yang dipakai query harian (status, foreign key).
- Seeder dibagi: `ReferenceSeeder` (tarif, template, admin), `DemoSeeder` (data demo). Production hanya menjalankan `ReferenceSeeder`.

## 4. Uang

- Satuan rupiah bulat.
- Pembagian komisi hanya lewat `Support\Money::split(int $amount, int $pct): array{platform:int, recipient:int}`:
  - `platform = round(amount * pct / 100)` (pembulatan ke rupiah terdekat, setengah ke atas)
  - `recipient = amount - platform`, sehingga jumlah selalu sama dengan `amount`.
- Format tampilan: `Rp50.000` (titik pemisah ribuan, tanpa desimal) lewat helper `Money::format()`.
- Tarif dan persentase komisi **disalin ke job atau transaksi** saat dibuat. Perubahan pengaturan tidak memengaruhi data lama.

## 5. Waktu

- Server memakai UTC. Acuan waktu selalu server, bukan perangkat.
- Tampilan WIB: `5 Okt 2026, 14.30 WIB`.
- Di test, bekukan waktu dengan `Carbon::setTestNow()` / `travelTo()`. Jangan memakai `sleep`.

## 6. Validasi dan otorisasi

- Validasi di `FormRequest` atau `rules()` Livewire. Aturan yang dipakai berulang menjadi `Rule` class di domain.
- Pesan validasi berbahasa Indonesia, menyebut penyebab dan perbaikan.
- Otorisasi wajib di setiap titik masuk (`authorize()` di controller, `$this->authorize()` di Livewire). Tidak ada rute data privat tanpa middleware `auth` dan policy.
- Jangan mempercayai input untuk hal yang bisa dihitung server (harga tagihan, status, `inspector_id`, `requester_id`).

## 7. Kesalahan

- Pelanggaran aturan bisnis melempar exception domain (`final class JobAlreadyTaken extends DomainException`), dengan `code` mesin (`JOB_ALREADY_TAKEN`) dan pesan Indonesia.
- Controller dan Livewire menerjemahkan exception domain menjadi respons (`409`, `422`, dll.) di satu tempat (handler), bukan `try/catch` di tiap layar.
- Kode error mengikuti PRD 19.11.

## 8. Livewire

- Satu komponen = satu layar atau satu blok interaktif. Komponen memanggil Action, tidak mengandung logika bisnis.
- Properti publik hanya data yang perlu. Jangan menaruh model sensitif utuh di properti publik. Pakai ID lalu muat ulang dan otorisasi.
- Setiap aksi di komponen memanggil `authorize()`.
- Pagination dan filter memakai `WithPagination` dan query string.

## 9. Gaya dan analisis statis

- Gaya: **Laravel Pint** (preset `laravel`). `composer lint` harus bersih.
- Analisis: **Larastan** level 5 pada awalnya, naik bertahap. `composer analyse` harus bersih.
- `declare(strict_types=1);` di semua file PHP baru.
- Class `final` secara default. Hanya buka (`non-final`) bila memang dirancang untuk diwarisi.

## 10. Git

Lihat [CONTRIBUTING.md](CONTRIBUTING.md).
