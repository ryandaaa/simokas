# Fitur: Kontrak Tagihan & Pembayaran (PAY-1)

| | |
| --- | --- |
| **Domain** | Payment (`shared`) |
| **Prioritas** | P0 |
| **Status** | Selesai |
| **Referensi PRD** | 13.4, 18.12, 19.5 |
| **Flow doc** | `docs/flows/payment.md` |

## 1. Ringkasan

Menyediakan kontrak publik `Payment\Contracts\Billing` yang digunakan oleh domain lain (khususnya `Inspection` untuk kartu `JOB-1`, serta kelak `Result` dan `Transaction`) untuk membuat tagihan dan memeriksa status tagihan secara idempoten, aman dari manipulasi klien, serta mengembalikan DTO publik (`BillData`), bukan model internal `Payment`.

Fitur ini juga menyediakan:
- Model dan migrasi tabel `payments` dengan batasan unik pada `idempotency_key`.
- Action bisnis idempoten: `CreateBill`, `ProcessPaymentResult`, dan `ExpirePayment`.
- Halaman antarmuka Livewire bertanda **SIMULASI** dengan pilihan metode bayar, dialog konfirmasi modal, pencegahan klik ganda, dan proteksi server di lingkungan produksi.
- Endpoint API simulasi `POST /payments/simulate` (dan `/api/v1/payments/simulate`) sesuai PRD 19.5.
- Event `PaymentSucceeded` kala lampau yang membawa ID dan tujuan pembayaran.

## 2. Kontrak Publik: `Payment\Contracts\Billing`

Interface: `App\Domains\Payment\Contracts\Billing`

```php
namespace App\Domains\Payment\Contracts;

use App\Domains\Payment\DTOs\BillData;
use App\Domains\Payment\DTOs\CreateBillData;

interface Billing
{
    /**
     * Membuat tagihan baru atau mengembalikan tagihan yang ada jika idempotency_key sama.
     * Nominal tagihan wajib dihitung di sisi server oleh domain pemanggil (PAY-R1).
     */
    public function createBill(CreateBillData $data): BillData;

    /**
     * Mencari data tagihan berdasarkan ID pembayaran (mengembalikan null jika tidak ditemukan).
     */
    public function findBill(string $paymentId): ?BillData;

    /**
     * Mengambil data tagihan atau melempar ModelNotFoundException jika tidak ditemukan.
     */
    public function getBill(string $paymentId): BillData;
}
```

### 2.1 DTO Masukan: `CreateBillData`

```php
namespace App\Domains\Payment\DTOs;

use App\Domains\Payment\Enums\PaymentPurpose;
use Carbon\CarbonInterface;

final readonly class CreateBillData
{
    public function __construct(
        public string $payerId,
        public PaymentPurpose $purpose,
        public string $refId,
        public int $amount,
        public string $idempotencyKey,
        public ?CarbonInterface $expiresAt = null,
    ) {}
}
```

### 2.2 DTO Keluaran: `BillData`

```php
namespace App\Domains\Payment\DTOs;

use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use Carbon\CarbonInterface;

final readonly class BillData
{
    public function __construct(
        public string $id,
        public string $payerId,
        public PaymentPurpose $purpose,
        public string $refId,
        public int $amount,
        public PaymentStatus $status,
        public ?PaymentMethod $method,
        public string $idempotencyKey,
        public ?CarbonInterface $expiresAt,
        public ?CarbonInterface $paidAt,
        public ?CarbonInterface $createdAt,
    ) {}
}
```

## 3. Contoh Penggunaan di Domain Lain (misal Inspection / JOB-1)

```php
use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentPurpose;

final class RequestInspection
{
    public function __construct(
        private readonly Billing $billing,
    ) {}

    public function handle(User $requester, Listing $listing, int $feeAmount, string $idempotencyKey): InspectionJob
    {
        // 1. Buat record job di domain Inspection...
        
        // 2. Buat tagihan via kontrak Billing
        $bill = $this->billing->createBill(new CreateBillData(
            payerId: $requester->id,
            purpose: PaymentPurpose::Inspection,
            refId: $job->id,
            amount: $feeAmount, // Dihitung di server dari setting snapshot, bukan dari input user
            idempotencyKey: $idempotencyKey,
            expiresAt: now()->addMinutes(30),
        ));

        // 3. Simpan relasi payment_id atau arahkan pengguna ke route('payments.show', $bill->id)
    }
}
```

## 4. Event: `PaymentSucceeded`

Ketika pembayaran simulasi berstatus `success`, event `App\Domains\Payment\Events\PaymentSucceeded` dipancarkan.
Event ini membawa data primitif dan enum, tanpa mengikat model internal:

- `paymentId` (string UUID)
- `purpose` (`PaymentPurpose`: `Inspection`, `Access`, `Purchase`)
- `refId` (string UUID)
- `amount` (int)
- `payerId` (string UUID)

Domain pendengar (seperti `Inspection` pada `JOB-1`) dapat mendengarkan event ini dan mengubah status job menjadi `paid`.

## 5. Keamanan & Idempotensi

1. **Idempotensi Penuh:** Penggunaan `idempotency_key` unik menjamin bahwa request ganda (klik ganda, refresh, replay jaringan) selalu mengembalikan objek tagihan yang sama tanpa membuat baris baru di basis data.
2. **Kunci Sama, Parameter Berbeda = 409:** Bila `idempotency_key` sudah dipakai tetapi `payer_id`, `purpose`, `ref_id`, atau `amount` berbeda dari tagihan yang tersimpan, permintaan ditolak dengan `409 IDEMPOTENCY_KEY_CONFLICT` (`IdempotencyKeyConflictException`). Pesan galat tidak membocorkan data tagihan milik orang lain. Hanya bila keempat parameter sama, tagihan yang ada dikembalikan. Pemeriksaan ini berlaku di semua jalur, termasuk pemulihan balapan konkurensi (pelanggaran batasan unik di dalam transaksi).
3. **Kunci Baris & Pembaruan Berkondisi:** Perubahan status dalam `ProcessPaymentResult` dilakukan dalam transaksi database dengan klausa berkondisi (`where status = status_lama`), sehingga pemanggilan konkuren tidak berefek ganda.
4. **Pemisahan Environment:** Mode pilih hasil simulasi bebas (gagal/tertunda) hanya diizinkan di non-produksi/demo. Di lingkungan produksi, server menolak pemilihan hasil bebas dan mewajibkan hasil default sistem (`success`).
5. **Otorisasi:** Hak melihat (`view`) dan membayar (`pay`) dilindungi `PaymentPolicy`. Hanya pemilik tagihan yang dapat mengakses dan membayar; pembayar lain ditolak dengan `403 Forbidden`. Tagihan yang melewati batas waktu kedaluwarsa (`expires_at`) ditolak dengan `410 Gone / PAYMENT_EXPIRED`.

## 6. Catatan untuk Pengguna Kontrak (JOB-1 dan lainnya)

- Setelah retry dari `failed`, kunci pada baris pembayaran **berganti** ke kunci baru, sedangkan kunci lama tidak lagi merujuk ke baris aktif. Pemanggil kontrak harus mengunci keunikan tagihan aktif berdasarkan `(purpose, ref_id, payer)` di sisinya (atau lewat metode kontrak yang tersedia) sebelum membuat tagihan baru, agar tidak tercipta tagihan ganda untuk acuan yang sama.
- Selalu bangkitkan `idempotencyKey` yang benar-benar baru (mis. UUID v4) untuk setiap tagihan baru; jangan memakai ulang kunci antar tagihan yang berbeda, karena akan ditolak dengan `409 IDEMPOTENCY_KEY_CONFLICT`.
- **PAY-T15:** bila tagihan yang masih dapat dibayar (atau sudah `success`) sudah ada untuk `(payer, purpose, ref_id)` yang sama, `createBill` mengembalikan tagihan itu alih-alih membuat baris baru. Tagihan lama yang sudah kedaluwarsa atau `refunded` tidak dipakai ulang.

## 7. Kontrak Publik: `Payment\Contracts\Ledger` (PAY-2)

Interface: `App\Domains\Payment\Contracts\Ledger` (implementasi `LedgerService`, singleton).

```php
interface Ledger
{
    public function postAccessPayout(string $paymentId, string $inspectorId, ?int $platformFeePct = null): void;
    public function postInspectionPayout(string $paymentId, string $inspectorId, int $platformFeePct): void;
    public function postPurchasePayout(string $paymentId, int $saleCommission, int $inspectorCommission, string $inspectorUserId): void;
    public function refundPayment(string $paymentId): void;
    public function balanceFor(string $inspectorId): int;
}
```

Desain argumen persentase (JOB-R6, JOB-E13): fee inspeksi diposting saat job `completed`, bisa berhari-hari setelah job dibuat, sehingga persentase
WAJIB disuplai pemanggil dari salinan `platform_fee_pct` pada job (JOB-4 memanggil `postInspectionPayout($paymentId, $inspectorId, $job->platform_fee_pct)`);
pengaturan saat ini TIDAK dibaca untuk jalur ini, dan pemanggilan tanpa persentase ditolak (`InvalidLedgerPostingException::snapshotRequired`).
Fee akses diposting segera setelah pembayaran `success`, sehingga pemanggil BOLEH menyuplai persentase salinan, atau `null` untuk memakai pengaturan
saat posting. Kedua jalur memvalidasi bilangan bulat 0..100 (`InvalidLedgerPostingException::invalidPercentage`; `Money::split` memvalidasi ulang)
dan mencatat persentase yang dipakai di `note` tiap entri untuk audit. Tanpa kolom baru: `note` yang sudah ada cukup.

### 7.1 Kapan masing-masing method dipanggil

| Method | Pemanggil | Kapan |
| --- | --- | --- |
| `postAccessPayout` | Listener `PostAccessLedger` (event `PaymentSucceeded` tujuan `access` yang membawa `beneficiaryId`), atau RES-4 eksplisit saat akses diberikan | Setelah pembayaran akses `success` |
| `postInspectionPayout` | JOB-4 | Saat job `completed` (bukan saat bayar) |
| `postPurchasePayout` | TRX-3 (saat serah terima dikonfirmasi) | Setelah pembayaran pembelian `success`; nominal dari salinan pada transaksi |
| `refundPayment` | JOB-3 (pembatalan setelah bayar) | Untuk pembayaran `success` yang belum diposting |
| `balanceFor` | Halaman Pendapatan, Dashboard (DSH-1) | Saldo = jumlah entri kredit inspektor |

### 7.2 Aturan posting

1. Pembagian memakai `Support\Money::split(amount, pct)`; persen fee inspeksi selalu dari argumen snapshot job, persen fee akses dari argumen bila
   disuplai atau dari `SettingsReader` saat posting bila `null`. Nilai hasil split tersimpan permanen di entri sehingga perubahan pengaturan tidak
   memengaruhi entri lama (persen yang dipakai juga dicatat di `note` kedua entri: `Komisi platform :pct% ...`).
2. Setiap posting menulis dua entri dalam satu statement: `platform_fee` (akun null = platform) dan fee penerima (`access_fee` / `inspection_fee`).
3. Posting ganda ditolak: `LedgerAlreadyPostedException` (409), diperkuat batasan unik `(payment_id, entry_type)`.
4. Hanya pembayaran `success` yang dapat diposting; pasangan tujuan–jenis entri harus cocok (`inspection` ↔ `inspection_fee`, `access` ↔ `access_fee`, `purchase` ↔ `platform_fee` + `sale_commission`).
5. Entri ledger append-only: update dan delete ditolak di kode lewat event model (`LedgerEntryImmutableException`, kode mesin `LEDGER_ENTRY_IMMUTABLE`).
   `ledger_entries.amount` bertipe signed sesuai PRD 18.12 (positif kredit, negatif debit).
6. Foreign key `ledger_entries.payment_id` dan `account_user_id` memakai `restrictOnDelete`: menghapus pembayaran atau pengguna yang punya entri
   ledger ditolak database (`QueryException`). Ledger tidak boleh hilang.

### 7.2b Aturan posting komisi penjualan (`postPurchasePayout`, TRX-2)

1. Nominal disuplai penuh oleh pemanggil dari salinan pada transaksi (contoh TRX-2: harga Rp100.000.000 → komisi Rp3.000.000 → platform
   Rp300.000, inspektor Rp2.700.000). Pengaturan saat ini TIDAK dibaca; tidak ada `Money::split` di jalur ini.
2. Hanya pembayaran `success` dengan tujuan `purchase`. Komisi tidak boleh negatif; bagian inspektor tidak boleh melebihi total komisi;
   total komisi tidak boleh melebihi nominal pembayaran (`InvalidLedgerPostingException`, 422).
3. Inspektor wajib ada dan berperan inspektor bila bagiannya > 0. Bila kedua komisi nol, pemanggilan adalah no-op (tidak ada entri ditulis).
4. Menulis `platform_fee` (akun null = platform, nilai = total komisi − bagian inspektor) dan, bila bagian inspektor > 0, `sale_commission`
   (akun = inspektor) dalam satu statement. Hanya komisi yang dicatat; bagian penjual tidak masuk ledger.
5. Posting ganda ditolak: `LedgerAlreadyPostedException` (409), diperkuat batasan unik `(payment_id, entry_type)`. Status pembayaran tidak diubah.

### 7.3 Aturan refund

1. Hanya pembayaran `success` yang belum diposting (PAY-R7). Sekali saja: pemanggilan kedua mengembalikan pembayaran apa adanya tanpa entri baru.
2. Refund menulis tepat satu entri `refund` (akun = pembayar, nilai = nilai pembayaran, kredit kepada pembayar) dan mengubah status menjadi `refunded`. Tidak ada entri pembalik karena belum ada yang diposting.
3. Seluruh operasi dalam satu `DB::transaction` (`lockForUpdate` + pembaruan berkondisi); kegagalan di tengah menggulirkan semuanya.
4. Invarian tetap berlaku: jumlah semua entri per pembayaran = nilai pembayaran, termasuk pembayaran yang di-refund.

## 8. Halaman Pendapatan Inspektor (PAY-2)

Rute `GET /inspector/earnings` (`inspector.earnings`), komponen Livewire `Payment\Livewire\InspectorEarnings`. Middleware `auth`, `not_blocked`, `role:inspector`, `verified_inspector`; otorisasi ganda lewat `LedgerEntryPolicy::viewEarnings`. Menampilkan total saldo (`Ledger::balanceFor`), rincian per sumber (`entry_type`), dan riwayat entri (12 per halaman) dengan `x-ui.money` dan `x-ui.datetime`, plus keadaan kosong, memuat, dan galat (tombol coba lagi). Tanpa penarikan nyata. Inspektor hanya melihat entri miliknya sendiri.
