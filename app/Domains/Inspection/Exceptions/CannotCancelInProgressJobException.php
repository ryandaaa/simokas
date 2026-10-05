<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Support\Exceptions\DomainException;

final class CannotCancelInProgressJobException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('inspection.errors.cannot_cancel_in_progress'),
            'CANNOT_CANCEL_IN_PROGRESS_JOB',
            422,
        );
    }
}
