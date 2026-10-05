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
2. **Kunci Baris & Pembaruan Berkondisi:** Perubahan status dalam `ProcessPaymentResult` dilakukan dalam transaksi database dengan klausa berkondisi (`where status = status_lama`), sehingga pemanggilan konkuren tidak berefek ganda.
3. **Pemisahan Environment:** Mode pilih hasil simulasi bebas (gagal/tertunda) hanya diizinkan di non-produksi/demo. Di lingkungan produksi, server menolak pemilihan hasil bebas dan mewajibkan hasil default sistem (`success`).
4. **Otorisasi:** Hak melihat (`view`) dan membayar (`pay`) dilindungi `PaymentPolicy`. Hanya pemilik tagihan yang dapat mengakses dan membayar; pembayar lain ditolak dengan `403 Forbidden`. Tagihan yang melewati batas waktu kedaluwarsa (`expires_at`) ditolak dengan `410 Gone / PAYMENT_EXPIRED`.
