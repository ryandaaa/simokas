<?php

declare(strict_types=1);

namespace App\Domains\Listing\Exceptions;

use App\Support\Exceptions\DomainException;

final class ListingValidationException extends DomainException
{
    public static function slotsRequired(): self
    {
        return new self(
            __('listing.validation.slots_required'),
            self::CODE_VALIDATION_ERROR,
            422
        );
    }

    public static function slotInPast(): self
    {
        return new self(
            __('listing.validation.slot_past'),
            self::CODE_VALIDATION_ERROR,
            422
        );
    }

    public static function invalidSeller(): self
    {
        return new self(
            __('listing.validation.owner_required'),
            self::CODE_NOT_ALLOWED,
            403
        );
    }

    public static function inspectorForbidden(): self
    {
        return new self(
            __('listing.validation.inspector_forbidden'),
            self::CODE_NOT_ALLOWED,
            403
        );
    }

    public static function adminForbidden(): self
    {
        return new self(
            __('listing.validation.admin_forbidden'),
            self::CODE_NOT_ALLOWED,
            403
        );
    }
}
