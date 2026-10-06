<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class TransactionNotPayableException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.transaction_not_payable'),
            'TRANSACTION_NOT_PAYABLE',
            409,
        );
    }
}
