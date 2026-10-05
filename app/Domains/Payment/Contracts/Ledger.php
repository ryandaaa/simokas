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
 */
interface Ledger
{
    /**
     * Mencatat bagi hasil biaya akses untuk inspektor dan platform.
     * Dipanggil saat akses hasil diberikan (RES-4).
     */
    public function postAccessPayout(string $paymentId, string $inspectorId): void;

    /**
     * Mencatat fee inspeksi untuk inspektor dan platform.
     * Dipanggil saat job completed (JOB-4).
     */
    public function postInspectionPayout(string $paymentId, string $inspectorId): void;

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
