<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Support\Exceptions\DomainException;

final class RequestNotAllowedException extends DomainException
{
    public static function inspector(): self
    {
        return new self(
            __('inspection.errors.inspector_forbidden'),
            self::CODE_NOT_ALLOWED,
            403,
        );
    }

    public static function admin(): self
    {
        return new self(
            __('inspection.errors.admin_forbidden'),
            self::CODE_NOT_ALLOWED,
            403,
        );
    }

    public static function blocked(): self
    {
        return new self(
            __('inspection.errors.blocked_forbidden'),
            self::CODE_NOT_ALLOWED,
            403,
        );
    }
}
