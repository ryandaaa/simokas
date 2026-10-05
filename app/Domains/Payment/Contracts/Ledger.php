<?php

declare(strict_types=1);

namespace App\Domains\Payment\Contracts;

/**
 * Kontrak publik Ledger untuk pencatatan bagi hasil dan refund.
 *
 * Dipakai lintas domain: JOB-4 memanggil postInspectionPayout saat job
 * completed, JOB-3 memanggil refundPayment saat pembatalan setelah bayar,
 * dan RES-4 memanggil postAccessPayout saat akses hasil diberikan.
 * Semua pemanggilan idempoten: posting ganda ditolak, refund ganda
 * berefek sekali.
 *
 * Desain argumen persentase (JOB-R6, JOB-E13):
 * - Fee inspeksi diposting saat job completed, bisa berhari-hari setelah job
 *   dibuat. Persentase WAJIB disuplai pemanggil dari salinan `platform_fee_pct`
 *   pada job (JOB-4). Pengaturan saat ini TIDAK dibaca untuk jalur ini.
 * - Fee akses diposting segera setelah pembayaran success. Pemanggil BOLEH
 *   menyuplai persentase (mis. salinan saat tagihan dibuat); bila null,
 *   dipakai pengaturan saat ini SAAT posting.
 * - Kedua jalur memvalidasi 0..100 bilangan bulat dan mencatat persentase
 *   yang dipakai di `note` tiap entri untuk audit. Perhitungan selalu lewat
 *   `Support\Money::split`.
 */
interface Ledger
{
    /**
     * Mencatat bagi hasil biaya akses untuk inspektor dan platform.
     * Dipanggil saat akses hasil diberikan (RES-4).
     *
     * @param  ?int  $platformFeePct  Persentase salinan pemanggil, atau null
     *                                untuk memakai pengaturan saat posting.
     */
    public function postAccessPayout(string $paymentId, string $inspectorId, ?int $platformFeePct = null): void;

    /**
     * Mencatat fee inspeksi untuk inspektor dan platform.
     * Dipanggil saat job completed (JOB-4).
     *
     * @param  int  $platformFeePct  Persentase salinan `platform_fee_pct` pada job.
     */
    public function postInspectionPayout(string $paymentId, string $inspectorId, int $platformFeePct): void;

    /**
     * Mengembalikan dana pembayaran success yang belum diposting.
     * Idempoten: pemanggilan kedua tidak menambah entri.
     */
    public function refundPayment(string $paymentId): void;

    /**
     * Saldo inspektor = jumlah entri kredit miliknya.
     */
    public function balanceFor(string $inspectorId): int;
}
