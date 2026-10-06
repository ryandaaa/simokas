<?php

declare(strict_types=1);

namespace App\Domains\Rating\Exceptions;

use App\Support\Exceptions\DomainException;

final class TransactionHasNoInspectorException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('rating.errors.no_inspector'),
            'TRANSACTION_HAS_NO_INSPECTOR',
            422,
        );
    }
}
