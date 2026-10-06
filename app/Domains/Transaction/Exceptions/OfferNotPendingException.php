<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class OfferNotPendingException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.offer_not_pending'),
            'OFFER_NOT_PENDING',
            409,
        );
    }
}
