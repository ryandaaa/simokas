<?php

declare(strict_types=1);

namespace App\Domains\Rating\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class RatingHidden
{
    use Dispatchable;

    public function __construct(
        public readonly string $ratingId,
    ) {}
}
