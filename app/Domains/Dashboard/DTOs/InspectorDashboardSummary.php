<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\DTOs;

final readonly class InspectorDashboardSummary
{
    public function __construct(
        public int $netEarnings,
        public int $previousMonthEarnings,
        public ?float $changePercent,
        public int $availableJobs,
        public int $activeJobs,
        public int $completedInMonth,
        public int $totalCompleted,
        public float $ratingAvg,
        public int $ratingCount,
    ) {}
}
