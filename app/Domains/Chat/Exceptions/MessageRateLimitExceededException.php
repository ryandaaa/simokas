<?php

declare(strict_types=1);

namespace App\Domains\Chat\Exceptions;

use App\Support\Exceptions\DomainException;

final class MessageRateLimitExceededException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('chat.errors.rate_limit_exceeded'),
            self::CODE_RATE_LIMITED,
            429,
        );
    }
}
