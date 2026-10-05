<?php

declare(strict_types=1);

namespace App\Domains\Result\Enums;

enum InspectionRecommendation: string
{
    case Recommended = 'recommended';
    case Caution = 'caution';
    case NotRecommended = 'not_recommended';

    public function label(): string
    {
        return match ($this) {
            self::Recommended => __('ui.score_ring.recommendations.recommended'),
            self::Caution => __('ui.score_ring.recommendations.caution'),
            self::NotRecommended => __('ui.score_ring.recommendations.not_recommended'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Recommended => 'success',
            self::Caution => 'warning',
            self::NotRecommended => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Recommended => 'centang',
            self::Caution => 'segitiga-peringatan',
            self::NotRecommended => 'silang',
        };
    }
}
