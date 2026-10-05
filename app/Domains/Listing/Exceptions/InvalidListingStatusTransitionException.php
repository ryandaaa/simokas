<?php

declare(strict_types=1);

namespace App\Domains\Listing\Exceptions;

use App\Domains\Listing\Enums\ListingStatus;
use App\Support\Exceptions\DomainException;

final class InvalidListingStatusTransitionException extends DomainException
{
    public static function fromStatus(ListingStatus|string $from, ListingStatus|string $to): self
    {
        $fromStr = $from instanceof ListingStatus ? $from->value : (string) $from;
        $toStr = $to instanceof ListingStatus ? $to->value : (string) $to;

        return new self(
            "Transisi status listing dari {$fromStr} ke {$toStr} tidak diperbolehkan.",
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['from' => $fromStr, 'to' => $toStr],
        );
    }

    public static function alreadyProcessed(ListingStatus|string $currentStatus): self
    {
        $statusStr = $currentStatus instanceof ListingStatus ? $currentStatus->value : (string) $currentStatus;

        return new self(
            __('listing.errors.already_processed', ['status' => $statusStr]),
            self::CODE_CONFLICT,
            409,
            null,
            ['current_status' => $statusStr],
        );
    }

    public static function cannotResubmit(ListingStatus|string $currentStatus): self
    {
        $statusStr = $currentStatus instanceof ListingStatus ? $currentStatus->value : (string) $currentStatus;

        return new self(
            __('listing.errors.cannot_resubmit', ['status' => $statusStr]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['current_status' => $statusStr],
        );
    }
}
