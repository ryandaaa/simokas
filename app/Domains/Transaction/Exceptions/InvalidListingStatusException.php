<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class InvalidListingStatusException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.invalid_listing_status'),
            'INVALID_LISTING_STATUS',
            422,
        );
    }
}
