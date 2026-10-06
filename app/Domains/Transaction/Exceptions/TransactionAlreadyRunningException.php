<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class TransactionAlreadyRunningException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.transaction_already_running'),
            'TRANSACTION_ALREADY_RUNNING',
            409,
        );
    }
}
