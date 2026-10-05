<?php

declare(strict_types=1);

namespace App\Domains\Setting\Contracts;

use App\Domains\Identity\Models\User;
use App\Domains\Setting\Exceptions\InvalidSettingValueException;

/**
 * Kontrak pembaruan pengaturan sistem.
 *
 * PENTING: Perubahan pengaturan hanya berlaku untuk job dan transaksi baru.
 * Nilai tarif dan komisi pada job atau transaksi lama telah tersalin (snapshot)
 * dan tidak berubah saat pengaturan diperbarui.
 */
interface UpdatesSettings
{
    /**
     * Memperbarui seluruh pengaturan dalam satu transaksi atomik.
     *
     * @param  array<string, int|string>  $settings
     *
     * @throws InvalidSettingValueException
     */
    public function handle(array $settings, ?User $updatedBy = null): void;
}
