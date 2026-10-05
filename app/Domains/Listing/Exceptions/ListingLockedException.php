<?php

declare(strict_types=1);

namespace App\Domains\Listing\Exceptions;

use App\Support\Exceptions\DomainException;

/**
 * Thrown when a listing cannot be changed because it is locked:
 * a core field is edited while a job or transaction is active (LST-R7),
 * every field is edited while in_transaction, deactivation is attempted
 * while a job or transaction is active (LST-R6), or a sold listing is
 * edited at all (LST-R8).
 */
final class ListingLockedException extends DomainException
{
    public static function coreFieldsLocked(): self
    {
        return new self(
            __('listing.errors.core_fields_locked'),
            'LISTING_HAS_ACTIVE_JOB',
            409,
        );
    }

    public static function allFieldsLockedInTransaction(): self
    {
        return new self(
            __('listing.errors.in_transaction_locked'),
            self::CODE_CONFLICT,
            409,
        );
    }

    public static function soldImmutable(): self
    {
        return new self(
            __('listing.errors.sold_immutable'),
            self::CODE_CONFLICT,
            409,
        );
    }

    public static function deactivationBlocked(): self
    {
        return new self(
            __('listing.errors.deactivation_blocked'),
            'LISTING_HAS_ACTIVE_JOB',
            409,
        );
    }
}
