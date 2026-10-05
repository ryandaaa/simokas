<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Services;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\Services\DefaultListingActivityChecker;

/**
 * Pengganti binding ListingActivityChecker untuk JOB-1.
 *
 * hasActiveJob() membaca tabel inspection_jobs (job bukan status akhir).
 * hasActiveTransaction() didelegasikan ke implementasi bawaan agar perilaku
 * Listing tetap utuh sampai domain Transaction menggantikannya (TRX-1).
 */
final class InspectionListingActivityChecker implements ListingActivityChecker
{
    public function __construct(
        private readonly DefaultListingActivityChecker $fallback,
    ) {}

    public function hasActiveJob(string $listingId): bool
    {
        return InspectionJob::query()
            ->where('listing_id', $listingId)
            ->whereNotIn('status', [
                JobStatus::Completed->value,
                JobStatus::Cancelled->value,
                JobStatus::Expired->value,
            ])
            ->exists();
    }

    public function hasActiveTransaction(string $listingId): bool
    {
        return $this->fallback->hasActiveTransaction($listingId);
    }
}
