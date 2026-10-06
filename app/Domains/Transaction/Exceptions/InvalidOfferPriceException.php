<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class InvalidOfferPriceException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.invalid_offer_price'),
            'INVALID_OFFER_PRICE',
            422,
        );
    }
}
