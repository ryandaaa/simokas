<?php

declare(strict_types=1);

namespace App\Domains\Result\Exceptions;

use App\Support\Exceptions\DomainException;

final class JobNotInProgressException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('inspection.form.job_not_in_progress'),
            'JOB_NOT_IN_PROGRESS',
            403,
        );
    }
}
