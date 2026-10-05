<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Support\Exceptions\DomainException;

final class ListingNotActiveException extends DomainException
{
    public static function create(string $status): self
    {
        return new self(
            __('inspection.errors.listing_not_active', ['status' => $status]),
            'LISTING_NOT_ACTIVE',
            422,
            null,
            ['status' => $status],
        );
    }
}
