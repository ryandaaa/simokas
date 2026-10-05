<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Support\Exceptions\DomainException;

final class InspectorSpecialtyMismatchException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('inspection.errors.specialty_mismatch'),
            'SPECIALTY_MISMATCH',
            403,
        );
    }
}
