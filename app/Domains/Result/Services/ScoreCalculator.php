<?php

declare(strict_types=1);

namespace App\Domains\Result\Services;

use App\Domains\Result\DTOs\ScoreResult;
use App\Domains\Result\Enums\InspectionRecommendation;
use App\Domains\Result\Enums\ItemRating;

final class ScoreCalculator
{
    /**
     * Rating numeric values per PRD 15.3 and docs/flows/result.md:
     * Baik (good) = 2
     * Cukup (fair) = 1
     * Buruk (poor) = 0
     */
    public const int VALUE_GOOD = 2;

    public const int VALUE_FAIR = 1;

    public const int VALUE_POOR = 0;

    /**
     * @param  iterable<int|string, array{weight?: int|null, rating?: ItemRating|string|null, is_critical?: bool|null}|object>  $items
     */
    public function calculate(iterable $items): ScoreResult
    {
        $actualWeightedSum = 0;
        $maxWeightedSum = 0;
        $hasCriticalFailure = false;

        foreach ($items as $item) {
            $weight = $this->resolveWeight($item);
            $rating = $this->resolveRating($item);
            $isCritical = $this->resolveIsCritical($item);

            $value = match ($rating) {
                ItemRating::Good, 'good' => self::VALUE_GOOD,
                ItemRating::Fair, 'fair' => self::VALUE_FAIR,
                ItemRating::Poor, 'poor' => self::VALUE_POOR,
                default => 0,
            };

            $isPoor = ($rating === ItemRating::Poor || $rating === 'poor');
            if ($isCritical && $isPoor) {
                $hasCriticalFailure = true;
            }

            $actualWeightedSum += ($value * $weight);
            $maxWeightedSum += (self::VALUE_GOOD * $weight);
        }

        if ($maxWeightedSum === 0) {
            return new ScoreResult(
                totalScore: 0.0,
                recommendation: InspectionRecommendation::NotRecommended,
                hasCriticalFailure: $hasCriticalFailure,
                actualWeightedSum: 0,
                maxWeightedSum: 0,
            );
        }

        // Skor total = (jumlah nilai berbobot / nilai maksimum berbobot) * 100
        $rawScore = ($actualWeightedSum / $maxWeightedSum) * 100;
        $totalScore = round($rawScore, 2);

        $recommendation = $this->determineRecommendation($totalScore, $hasCriticalFailure);

        return new ScoreResult(
            totalScore: $totalScore,
            recommendation: $recommendation,
            hasCriticalFailure: $hasCriticalFailure,
            actualWeightedSum: $actualWeightedSum,
            maxWeightedSum: $maxWeightedSum,
        );
    }

    private function determineRecommendation(float $score, bool $hasCriticalFailure): InspectionRecommendation
    {
        if ($hasCriticalFailure) {
            return InspectionRecommendation::NotRecommended;
        }

        if ($score >= 80.0) {
            return InspectionRecommendation::Recommended;
        }

        if ($score >= 60.0) {
            return InspectionRecommendation::Caution;
        }

        return InspectionRecommendation::NotRecommended;
    }

    private function resolveWeight(mixed $item): int
    {
        if (is_array($item)) {
            $w = $item['weight'] ?? 1;
        } elseif (is_object($item)) {
            $w = $item->weight ?? 1;
        } else {
            $w = 1;
        }

        return max(1, (int) $w);
    }

    private function resolveRating(mixed $item): mixed
    {
        if (is_array($item)) {
            return $item['rating'] ?? null;
        }

        if (is_object($item)) {
            return $item->rating ?? null;
        }

        return null;
    }

    private function resolveIsCritical(mixed $item): bool
    {
        if (is_array($item)) {
            return (bool) ($item['is_critical'] ?? false);
        }

        if (is_object($item)) {
            return (bool) ($item->is_critical ?? false);
        }

        return false;
    }
}
