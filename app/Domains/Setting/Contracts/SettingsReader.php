<?php

declare(strict_types=1);

namespace App\Domains\Setting\Contracts;

use App\Domains\Setting\DTOs\SettingsData;

/**
 * Kontrak pembaca pengaturan sistem untuk domain lain.
 *
 * PENTING: Perubahan pengaturan hanya berlaku untuk job dan transaksi baru.
 * Tarif dan persentase komisi disalin (snapshot) ke setiap job atau transaksi
 * saat entitas tersebut dibuat, sehingga data lama tidak terpengaruh oleh
 * perubahan pengaturan di masa mendatang.
 */
interface SettingsReader
{
    /**
     * Tarif inspeksi mobil dalam Rupiah (bilangan bulat > 0).
     * Nilai yang sama berlaku sebagai biaya akses laporan mobil.
     */
    public function getInspectionFeeCar(): int;

    /**
     * Tarif inspeksi motor dalam Rupiah (bilangan bulat > 0).
     * Nilai yang sama berlaku sebagai biaya akses laporan motor.
     */
    public function getInspectionFeeMotorcycle(): int;

    /**
     * Persentase potongan komisi platform (0–100%).
     */
    public function getPlatformFeePct(): int;

    /**
     * Persentase komisi transaksi untuk inspektor (0–100%).
     */
    public function getSaleCommissionPct(): int;

    /**
     * Batas waktu (hari) sebelum job 'result_submitted' otomatis menjadi 'completed'.
     */
    public function getAutoAcceptDays(): int;

    /**
     * Batas waktu (hari) sebelum job 'paid' yang tidak diambil otomatis dibatalkan.
     */
    public function getPaidJobTimeoutDays(): int;

    /**
     * Mengambil seluruh nilai pengaturan dalam bentuk DTO publik.
     */
    public function all(): SettingsData;
}
