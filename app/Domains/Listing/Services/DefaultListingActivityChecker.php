<?php

declare(strict_types=1);

namespace App\Domains\Listing\Services;

use App\Domains\Listing\Contracts\ListingActivityChecker;

/**
 * Default activity checker used until JOB-1 (Inspection) and TRX-1
 * (Transaction) provide real implementations.
 *
 * Reports no activity so locks only apply from the listing status itself.
 * JOB-1 and TRX-1 override this binding without touching Listing code.
 */
final class DefaultListingActivityChecker implements ListingActivityChecker
{
    public function hasActiveJob(string $listingId): bool
    {
        return false;
    }

    public function hasActiveTransaction(string $listingId): bool
    {
        return false;
    }
}
