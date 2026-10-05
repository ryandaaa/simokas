<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Support\Exceptions\DomainException;

final class OwnListingJobException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('inspection.errors.own_listing'),
            'OWN_LISTING',
            403,
        );
    }
}
