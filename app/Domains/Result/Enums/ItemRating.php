<?php

declare(strict_types=1);

namespace App\Domains\Result\Enums;

enum ItemRating: string
{
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';

    public function label(): string
    {
        return match ($this) {
            self::Good => __('inspection.form.rating_good'),
            self::Fair => __('inspection.form.rating_fair'),
            self::Poor => __('inspection.form.rating_poor'),
        };
    }

    public function score(): int
    {
        return match ($this) {
            self::Good => 2,
            self::Fair => 1,
            self::Poor => 0,
        };
    }
}
