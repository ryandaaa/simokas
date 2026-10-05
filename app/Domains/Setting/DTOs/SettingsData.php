<?php

declare(strict_types=1);

namespace App\Domains\Setting\DTOs;

use Carbon\CarbonInterface;

final readonly class SettingsData
{
    public function __construct(
        public int $inspectionFeeCar,
        public int $inspectionFeeMotorcycle,
        public int $platformFeePct,
        public int $saleCommissionPct,
        public int $autoAcceptDays,
        public int $paidJobTimeoutDays,
        public ?string $updatedBy = null,
        public ?string $updatedByName = null,
        public ?CarbonInterface $updatedAt = null,
    ) {}

    /**
     * @return array{
     *     inspection_fee_car: int,
     *     inspection_fee_motorcycle: int,
     *     platform_fee_pct: int,
     *     sale_commission_pct: int,
     *     auto_accept_days: int,
     *     paid_job_timeout_days: int,
     *     updated_by: ?string,
     *     updated_by_name: ?string,
     *     updated_at: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'inspection_fee_car' => $this->inspectionFeeCar,
            'inspection_fee_motorcycle' => $this->inspectionFeeMotorcycle,
            'platform_fee_pct' => $this->platformFeePct,
            'sale_commission_pct' => $this->saleCommissionPct,
            'auto_accept_days' => $this->autoAcceptDays,
            'paid_job_timeout_days' => $this->paidJobTimeoutDays,
            'updated_by' => $this->updatedBy,
            'updated_by_name' => $this->updatedByName,
            'updated_at' => $this->updatedAt?->toIso8601String(),
        ];
    }
}
