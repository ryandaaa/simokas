<?php

declare(strict_types=1);

namespace App\Domains\Chat\Exceptions;

use App\Support\Exceptions\DomainException;

final class InvalidMessageException extends DomainException
{
    public static function empty(): self
    {
        return new self(
            __('chat.errors.message_empty'),
            self::CODE_VALIDATION_ERROR,
            422,
        );
    }

    public static function tooLong(): self
    {
        return new self(
            __('chat.errors.message_too_long'),
            self::CODE_VALIDATION_ERROR,
            422,
        );
    }
}
