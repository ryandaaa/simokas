<?php

declare(strict_types=1);

namespace App\Domains\Payment\Contracts;

use App\Domains\Payment\DTOs\BillData;
use App\Domains\Payment\DTOs\CreateBillData;

/**
 * Kontrak publik Billing untuk membuat dan memeriksa tagihan pembayaran.
 *
 * Digunakan oleh domain Inspection (JOB-1), Result, dan Transaction.
 * Semua nominal tagihan dihitung oleh server/pemanggil tepercaya,
 * bukan dari masukan klien, dan bersifat idempoten terhadap idempotency_key.
 */
interface Billing
{
    /**
     * Membuat tagihan baru atau mengembalikan tagihan yang ada jika idempotency_key sama.
     */
    public function createBill(CreateBillData $data): BillData;

    /**
     * Mencari data tagihan berdasarkan ID pembayaran.
     */
    public function findBill(string $paymentId): ?BillData;

    /**
     * Mengambil data tagihan berdasarkan ID pembayaran atau melempar pengecualian jika tidak ditemukan.
     */
    public function getBill(string $paymentId): BillData;
}
