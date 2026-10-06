<?php

declare(strict_types=1);

namespace App\Domains\Rating\Exceptions;

use App\Support\Exceptions\DomainException;

final class UnauthorizedBuyerException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('rating.errors.unauthorized'),
            'UNAUTHORIZED_BUYER',
            403,
        );
    }
}
