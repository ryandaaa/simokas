<?php

declare(strict_types=1);

namespace App\Domains\Setting\Services;

use App\Domains\Setting\Contracts\SettingsReader;
use App\Domains\Setting\DTOs\SettingsData;
use App\Domains\Setting\Enums\SettingKey;
use App\Domains\Setting\Models\AppSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class DatabaseSettingsReader implements SettingsReader
{
    public function getInspectionFeeCar(): int
    {
        return $this->getSettingValue(SettingKey::InspectionFeeCar);
    }

    public function getInspectionFeeMotorcycle(): int
    {
        return $this->getSettingValue(SettingKey::InspectionFeeMotorcycle);
    }

    public function getPlatformFeePct(): int
    {
        return $this->getSettingValue(SettingKey::PlatformFeePct);
    }

    public function getSaleCommissionPct(): int
    {
        return $this->getSettingValue(SettingKey::SaleCommissionPct);
    }

    public function getAutoAcceptDays(): int
    {
        return $this->getSettingValue(SettingKey::AutoAcceptDays);
    }

    public function getPaidJobTimeoutDays(): int
    {
        return $this->getSettingValue(SettingKey::PaidJobTimeoutDays);
    }

    public function all(): SettingsData
    {
        /** @var Collection<string, AppSetting> $settings */
        $settings = AppSetting::with('updater')
            ->get()
            ->keyBy('key');

        /** @var AppSetting|null $latestUpdated */
        $latestUpdated = $settings
            ->filter(fn (AppSetting $s): bool => $s->updated_at !== null)
            ->sortByDesc('updated_at')
            ->first();

        $updatedBy = $latestUpdated?->updated_by;
        $updatedByName = $latestUpdated?->updater?->name;
        /** @var CarbonInterface|null $updatedAt */
        $updatedAt = $latestUpdated?->updated_at;

        return new SettingsData(
            inspectionFeeCar: $this->resolveValue($settings, SettingKey::InspectionFeeCar),
            inspectionFeeMotorcycle: $this->resolveValue($settings, SettingKey::InspectionFeeMotorcycle),
            platformFeePct: $this->resolveValue($settings, SettingKey::PlatformFeePct),
            saleCommissionPct: $this->resolveValue($settings, SettingKey::SaleCommissionPct),
            autoAcceptDays: $this->resolveValue($settings, SettingKey::AutoAcceptDays),
            paidJobTimeoutDays: $this->resolveValue($settings, SettingKey::PaidJobTimeoutDays),
            updatedBy: $updatedBy,
            updatedByName: $updatedByName,
            updatedAt: $updatedAt,
        );
    }

    private function getSettingValue(SettingKey $key): int
    {
        $setting = AppSetting::query()->where('key', $key->value)->first();

        if ($setting === null) {
            return $key->defaultValue();
        }

        return $setting->intValue();
    }

    /**
     * @param  Collection<string, AppSetting>  $settings
     */
    private function resolveValue(Collection $settings, SettingKey $key): int
    {
        $setting = $settings->get($key->value);

        if ($setting === null) {
            return $key->defaultValue();
        }

        return $setting->intValue();
    }
}
