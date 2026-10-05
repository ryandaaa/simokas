<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Support\Exceptions\DomainException;

final class InvalidScheduleTimeException extends DomainException
{
    public static function mustBeInFuture(): self
    {
        return new self(
            __('inspection.errors.schedule_must_be_in_future'),
            'INVALID_SCHEDULE_TIME',
            422,
        );
    }
}
