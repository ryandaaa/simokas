<?php

declare(strict_types=1);

namespace App\Domains\Rating\Exceptions;

use App\Support\Exceptions\DomainException;

final class RatingAlreadySubmittedException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('rating.errors.already_submitted'),
            'RATING_ALREADY_SUBMITTED',
            409,
        );
    }
}
