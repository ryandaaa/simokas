<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Support\Exceptions\DomainException;

final class JobAlreadyTakenException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('inspection.errors.job_already_taken'),
            'JOB_ALREADY_TAKEN',
            409,
        );
    }
}
