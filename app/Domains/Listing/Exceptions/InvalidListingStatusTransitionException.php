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
}
