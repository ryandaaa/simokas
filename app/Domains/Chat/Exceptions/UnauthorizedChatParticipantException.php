<?php

declare(strict_types=1);

namespace App\Domains\Chat\Exceptions;

use App\Support\Exceptions\DomainException;

final class UnauthorizedChatParticipantException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('chat.errors.not_participant'),
            self::CODE_NOT_ALLOWED,
            403,
        );
    }
}
