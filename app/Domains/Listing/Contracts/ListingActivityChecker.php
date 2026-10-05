<?php

declare(strict_types=1);

namespace App\Domains\Listing\Contracts;

/**
 * Read-only view into activities from other domains that lock a listing.
 *
 * The Inspection (JOB-1) and Transaction (TRX-1) domains will replace the
 * default binding with real implementations. The Listing domain never
 * writes to those domains; it only asks whether a lock applies.
 */
interface ListingActivityChecker
{
    /**
     * Whether the listing has an active inspection job
     * (a job that is not in a final state).
     */
    public function hasActiveJob(string $listingId): bool;

    /**
     * Whether the listing has an active sale transaction
     * (offered, accepted, or paid-held, not final).
     */
    public function hasActiveTransaction(string $listingId): bool;
}
