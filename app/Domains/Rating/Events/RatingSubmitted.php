<?php

declare(strict_types=1);

namespace App\Domains\Rating\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class RatingSubmitted
{
    use Dispatchable;

    public function __construct(
        public readonly string $ratingId,
    ) {}
}
