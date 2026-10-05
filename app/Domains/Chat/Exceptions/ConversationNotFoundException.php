<?php

declare(strict_types=1);

namespace App\Domains\Chat\Exceptions;

use App\Support\Exceptions\DomainException;

final class ConversationNotFoundException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('chat.errors.not_found'),
            self::CODE_NOT_FOUND,
            404,
        );
    }

    public static function contextNotFound(): self
    {
        return new self(
            __('chat.errors.context_not_found'),
            self::CODE_NOT_FOUND,
            404,
        );
    }
}
