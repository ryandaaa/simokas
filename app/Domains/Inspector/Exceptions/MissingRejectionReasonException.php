<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Exceptions;

use App\Support\Exceptions\DomainException;

final class MissingRejectionReasonException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('inspector.errors.missing_rejection_reason'),
            self::CODE_VALIDATION_ERROR,
            422,
        );
    }
}
