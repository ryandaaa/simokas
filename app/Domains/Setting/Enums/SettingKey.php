<?php

declare(strict_types=1);

namespace App\Domains\Setting\Enums;

enum SettingKey: string
{
    case InspectionFeeCar = 'inspection_fee_car';
    case InspectionFeeMotorcycle = 'inspection_fee_motorcycle';
    case PlatformFeePct = 'platform_fee_pct';
    case SaleCommissionPct = 'sale_commission_pct';
    case AutoAcceptDays = 'auto_accept_days';
    case PaidJobTimeoutDays = 'paid_job_timeout_days';

    /**
     * Nilai bawaan pengaturan sesuai PRD 18.17 dan keputusan D-01, D-03.
     */
    public function defaultValue(): int
    {
        return match ($this) {
            self::InspectionFeeCar => 50000,
            self::InspectionFeeMotorcycle => 15000,
            self::PlatformFeePct => 10,
            self::SaleCommissionPct => 3,
            self::AutoAcceptDays => 3,
            self::PaidJobTimeoutDays => 7,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::InspectionFeeCar => __('setting.keys.inspection_fee_car'),
            self::InspectionFeeMotorcycle => __('setting.keys.inspection_fee_motorcycle'),
            self::PlatformFeePct => __('setting.keys.platform_fee_pct'),
            self::SaleCommissionPct => __('setting.keys.sale_commission_pct'),
            self::AutoAcceptDays => __('setting.keys.auto_accept_days'),
            self::PaidJobTimeoutDays => __('setting.keys.paid_job_timeout_days'),
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::InspectionFeeCar => __('setting.hints.inspection_fee_car'),
            self::InspectionFeeMotorcycle => __('setting.hints.inspection_fee_motorcycle'),
            self::PlatformFeePct => __('setting.hints.platform_fee_pct'),
            self::SaleCommissionPct => __('setting.hints.sale_commission_pct'),
            self::AutoAcceptDays => __('setting.hints.auto_accept_days'),
            self::PaidJobTimeoutDays => __('setting.hints.paid_job_timeout_days'),
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::InspectionFeeCar, self::InspectionFeeMotorcycle => __('setting.units.rupiah'),
            self::PlatformFeePct, self::SaleCommissionPct => __('setting.units.percent'),
            self::AutoAcceptDays, self::PaidJobTimeoutDays => __('setting.units.days'),
        };
    }

    /**
     * Validasi nilai sesuai aturan bisnis ADM-R3:
     * - Tarif: bilangan bulat > 0
     * - Persen: bilangan bulat 0–100
     * - Hari: bilangan bulat >= 1
     */
    public function isValid(mixed $value): bool
    {
        if (! is_int($value) && ! (is_string($value) && ctype_digit(ltrim($value, '+')))) {
            return false;
        }

        $intVal = (int) $value;

        return match ($this) {
            self::InspectionFeeCar, self::InspectionFeeMotorcycle => $intVal > 0,
            self::PlatformFeePct, self::SaleCommissionPct => $intVal >= 0 && $intVal <= 100,
            self::AutoAcceptDays, self::PaidJobTimeoutDays => $intVal >= 1,
        };
    }

    /**
     * @return array<int, string>
     */
    public function validationRules(): array
    {
        return match ($this) {
            self::InspectionFeeCar, self::InspectionFeeMotorcycle => ['required', 'integer', 'min:1'],
            self::PlatformFeePct, self::SaleCommissionPct => ['required', 'integer', 'min:0', 'max:100'],
            self::AutoAcceptDays, self::PaidJobTimeoutDays => ['required', 'integer', 'min:1'],
        };
    }
}
