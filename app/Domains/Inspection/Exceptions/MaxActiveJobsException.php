<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Support\Exceptions\DomainException;

final class MaxActiveJobsException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('inspection.errors.max_active_jobs'),
            'MAX_ACTIVE_JOBS',
            409,
        );
    }
}
