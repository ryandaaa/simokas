<?php

declare(strict_types=1);

namespace App\Domains\Result\DTOs;

use App\Domains\Result\Enums\InspectionRecommendation;

final readonly class ScoreResult
{
    public function __construct(
        public float $totalScore,
        public InspectionRecommendation $recommendation,
        public bool $hasCriticalFailure = false,
        public int $actualWeightedSum = 0,
        public int $maxWeightedSum = 0,
    ) {}
}
