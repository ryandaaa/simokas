<?php

declare(strict_types=1);

namespace App\Domains\Chat\Exceptions;

use App\Support\Exceptions\DomainException;

final class ChatWithSelfException extends DomainException
{
    public static function create(bool $isOwnListing = false): self
    {
        return new self(
            $isOwnListing ? __('chat.errors.own_listing') : __('chat.errors.cannot_chat_with_self'),
            'CHAT_WITH_SELF_NOT_ALLOWED',
            403,
        );
    }
}
