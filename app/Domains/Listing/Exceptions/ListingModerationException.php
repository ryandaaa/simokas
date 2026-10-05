<?php

declare(strict_types=1);

namespace App\Domains\Listing\Exceptions;

use App\Support\Exceptions\DomainException;

final class ListingModerationException extends DomainException
{
    public static function notAdmin(): self
    {
        return new self(
            __('listing.errors.moderation_not_allowed'),
            self::CODE_NOT_ALLOWED,
            403,
        );
    }

    public static function selfApproval(): self
    {
        return new self(
            __('listing.errors.self_approval'),
            self::CODE_NOT_ALLOWED,
            403,
        );
    }

    public static function notOwner(): self
    {
        return new self(
            __('listing.errors.resubmit_not_owner'),
            self::CODE_NOT_ALLOWED,
            403,
        );
    }

    public static function forbidden(): self
    {
        return new self(
            __('listing.errors.update_forbidden'),
            self::CODE_NOT_ALLOWED,
            403,
        );
    }
}
