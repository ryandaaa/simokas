<?php

declare(strict_types=1);

namespace App\Domains\Result\Exceptions;

use App\Support\Exceptions\DomainException;

final class InvalidRatingException extends DomainException
{
    public static function create(string $rating): self
    {
        return new self(
            __('inspection.form.invalid_rating', ['rating' => $rating]),
            'INVALID_RATING',
            422,
        );
    }
}
