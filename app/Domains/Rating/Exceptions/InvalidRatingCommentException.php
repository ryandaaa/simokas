<?php

declare(strict_types=1);

namespace App\Domains\Rating\Exceptions;

use App\Support\Exceptions\DomainException;

final class InvalidRatingCommentException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('rating.errors.invalid_comment'),
            'INVALID_RATING_COMMENT',
            422,
        );
    }
}
