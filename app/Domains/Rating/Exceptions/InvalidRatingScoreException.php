<?php

declare(strict_types=1);

namespace App\Domains\Rating\Exceptions;

use App\Support\Exceptions\DomainException;

final class InvalidRatingScoreException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('rating.errors.invalid_score'),
            'INVALID_RATING_SCORE',
            422,
        );
    }
}
