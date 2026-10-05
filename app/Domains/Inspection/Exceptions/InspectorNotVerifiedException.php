<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Support\Exceptions\DomainException;

final class InspectorNotVerifiedException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('inspection.errors.inspector_not_verified'),
            'INSPECTOR_NOT_VERIFIED',
            403,
        );
    }
}
