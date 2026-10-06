<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class HandoverNotAvailableException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.handover_not_available'),
            'HANDOVER_NOT_AVAILABLE',
            409,
        );
    }
}
