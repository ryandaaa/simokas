<?php

declare(strict_types=1);

namespace App\Domains\Identity\Exceptions;

use App\Support\Exceptions\DomainException;

final class CannotBlockSelfException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('identity.errors.cannot_block_self'),
            'CANNOT_BLOCK_SELF',
            422,
        );
    }
}
