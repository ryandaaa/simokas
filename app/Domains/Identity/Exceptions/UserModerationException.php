<?php

declare(strict_types=1);

namespace App\Domains\Identity\Exceptions;

use App\Support\Exceptions\DomainException;

final class UserModerationException extends DomainException
{
    public static function reasonRequired(): self
    {
        return new self(
            __('identity.errors.block_reason_required'),
            'BLOCK_REASON_REQUIRED',
            422,
        );
    }

    public static function notBlocked(): self
    {
        return new self(
            __('identity.errors.user_not_blocked'),
            'USER_NOT_BLOCKED',
            422,
        );
    }

    public static function unauthorized(): self
    {
        return new self(
            __('identity.errors.moderation_unauthorized'),
            'MODERATION_UNAUTHORIZED',
            403,
        );
    }
}
