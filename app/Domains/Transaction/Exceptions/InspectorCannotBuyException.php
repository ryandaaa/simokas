<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class InspectorCannotBuyException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.inspector_cannot_buy'),
            'INSPECTOR_CANNOT_BUY',
            422,
        );
    }
}
