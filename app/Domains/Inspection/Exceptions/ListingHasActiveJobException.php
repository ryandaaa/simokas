<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Support\Exceptions\DomainException;

final class ListingHasActiveJobException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('inspection.errors.listing_has_active_job'),
            'LISTING_HAS_ACTIVE_JOB',
            409,
        );
    }
}
