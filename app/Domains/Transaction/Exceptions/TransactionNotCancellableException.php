<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class TransactionNotCancellableException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.transaction_not_cancellable'),
            'TRANSACTION_NOT_CANCELLABLE',
            409,
        );
    }

    public static function reasonRequired(): self
    {
        return new self(
            __('transaction.errors.cancel_reason_required'),
            'CANCEL_REASON_REQUIRED',
            422,
        );
    }
}
