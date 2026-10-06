<?php

declare(strict_types=1);

namespace App\Domains\Listing\Services;

use App\Domains\Listing\Contracts\ListingActivityChecker;

/**
 * Default activity checker. hasActiveTransaction() is real since TRX-1:
 * it reads the transactions table (read-only) for offered, accepted, or
 * paid_held statuses. hasActiveJob() stays false here; JOB-1 overrides
 * the binding with InspectionListingActivityChecker.
 *
 * Reports no job activity so listing locks only apply from the listing
 * status itself for jobs.
 */
final class DefaultListingActivityChecker implements ListingActivityChecker
{
    public function hasActiveJob(string $listingId): bool
    {
        return false;
    }

    public function hasActiveTransaction(string $listingId): bool
    {
        // Read-only cross-domain read (A1): TRX-1 real implementation.
        // A listing is locked while a transaction is offered, accepted,
        // or paid_held (LST-R7).
        return \App\Domains\Transaction\Models\Transaction::query()
            ->where('listing_id', $listingId)
            ->whereIn('status', ['offered', 'accepted', 'paid_held'])
            ->exists();
    }
}
