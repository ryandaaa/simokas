<?php

declare(strict_types=1);

namespace App\Domains\Chat\Exceptions;

use App\Support\Exceptions\DomainException;

final class BlockedUserCannotChatException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('chat.errors.user_blocked'),
            'USER_BLOCKED',
            403,
        );
    }
}
