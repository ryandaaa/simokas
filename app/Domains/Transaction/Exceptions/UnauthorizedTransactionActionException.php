<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class UnauthorizedTransactionActionException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.not_authorized'),
            'TRANSACTION_FORBIDDEN',
            403,
        );
    }
}
