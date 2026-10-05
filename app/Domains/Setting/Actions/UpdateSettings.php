<?php

declare(strict_types=1);

namespace App\Domains\Setting\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Setting\Contracts\UpdatesSettings;
use App\Domains\Setting\Enums\SettingKey;
use App\Domains\Setting\Exceptions\InvalidSettingValueException;
use App\Domains\Setting\Models\AppSetting;
use Illuminate\Support\Facades\DB;

final class UpdateSettings implements UpdatesSettings
{
    /**
     * Memperbarui seluruh pengaturan sistem secara atomik dalam satu transaksi.
     *
     * @param  array<string, int|string>  $settings
     *
     * @throws InvalidSettingValueException
     */
    public function handle(array $settings, ?User $updatedBy = null): void
    {
        $validatedData = $this->validateAll($settings);

        DB::transaction(function () use ($validatedData, $updatedBy): void {
            $now = now();
            foreach ($validatedData as $key => $value) {
                AppSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'updated_by' => $updatedBy?->id,
                        'updated_at' => $now,
                    ]
                );
            }
        });
    }

    /**
     * Memvalidasi seluruh kumpulan pengaturan sebelum menyentuh database.
     * ADM-R3: Persentase 0–100 bulat. Tarif bilangan bulat > 0. Hari >= 1.
     *
     * @param  array<string, int|string>  $settings
     * @return array<string, int>
     *
     * @throws InvalidSettingValueException
     */
    private function validateAll(array $settings): array
    {
        $validated = [];

        foreach (SettingKey::cases() as $keyEnum) {
            $key = $keyEnum->value;

            if (! array_key_exists($key, $settings)) {
                throw new InvalidSettingValueException(
                    message: "Pengaturan '{$key}' wajib diisi.",
                    context: ['missing_key' => $key]
                );
            }

            $rawValue = $settings[$key];

            if (! $keyEnum->isValid($rawValue)) {
                $message = match ($keyEnum) {
                    SettingKey::InspectionFeeCar, SettingKey::InspectionFeeMotorcycle => "Tarif untuk '{$keyEnum->label()}' harus berupa bilangan bulat lebih dari 0.",
                    SettingKey::PlatformFeePct, SettingKey::SaleCommissionPct => "Persentase komisi untuk '{$keyEnum->label()}' harus berupa bilangan bulat antara 0 dan 100.",
                    SettingKey::AutoAcceptDays, SettingKey::PaidJobTimeoutDays => "Parameter waktu untuk '{$keyEnum->label()}' harus berupa bilangan bulat minimal 1 hari.",
                };

                throw new InvalidSettingValueException(
                    message: $message,
                    context: ['key' => $key, 'invalid_value' => $rawValue]
                );
            }

            $validated[$key] = (int) $rawValue;
        }

        return $validated;
    }
}
