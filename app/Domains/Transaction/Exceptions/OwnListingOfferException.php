<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class OwnListingOfferException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.cannot_buy_own_listing'),
            'CANNOT_BUY_OWN_LISTING',
            422,
        );
    }
}
