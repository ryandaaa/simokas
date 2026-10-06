<?php

declare(strict_types=1);

namespace App\Domains\Payment\Contracts;

/**
 * Kontrak publik Ledger untuk pencatatan bagi hasil dan refund.
 *
 * Dipakai lintas domain: JOB-4 memanggil postInspectionPayout saat job
 * completed, JOB-3 memanggil refundPayment saat pembatalan setelah bayar,
 * RES-4 memanggil postAccessPayout saat akses hasil diberikan, dan TRX-3
 * memanggil postPurchasePayout saat serah terima dikonfirmasi.
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
 *
 * Desain nominal komisi penjualan (TRX-2):
 * - Komisi penjualan diposting saat serah terima dikonfirmasi, berhari-hari
 *   setelah penawaran diterima. Nominal WAJIB disuplai pemanggil dari salinan
 *   pada transaksi (TRX-2). Pengaturan saat ini TIDAK dibaca untuk jalur ini,
 *   dan tidak ada `Money::split`: pemanggil sudah menghitung pembagiannya.
 * - Hanya komisi yang dicatat di ledger; bagian penjual tidak masuk ledger.
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
     * Mencatat komisi penjualan untuk platform dan inspektor.
     * Dipanggil saat serah terima dikonfirmasi (TRX-3).
     *
     * Nominal disuplai penuh oleh pemanggil dari salinan pada transaksi;
     * pengaturan saat ini TIDAK dibaca dan tidak ada Money::split di sini.
     * Platform menerima selisih saleCommission - inspectorCommission.
     * Hanya komisi yang dicatat; bagian penjual tidak masuk ledger.
     * Kedua komisi nol berarti no-op (tidak ada entri ditulis).
     *
     * @param  int  $saleCommission  Total komisi penjualan (integer rupiah, >= 0, <= nominal pembayaran).
     * @param  int  $inspectorCommission  Bagian inspektor (>= 0, <= saleCommission).
     * @param  string  $inspectorUserId  Wajib pengguna inspektor yang valid bila inspectorCommission > 0.
     */
    public function postPurchasePayout(string $paymentId, int $saleCommission, int $inspectorCommission, string $inspectorUserId): void;

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
