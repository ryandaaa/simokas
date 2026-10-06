<?php

declare(strict_types=1);

namespace App\Domains\Rating\Exceptions;

use App\Support\Exceptions\DomainException;

final class TransactionNotCompletedException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('rating.errors.not_completed'),
            'TRANSACTION_NOT_COMPLETED',
            409,
        );
    }
}
