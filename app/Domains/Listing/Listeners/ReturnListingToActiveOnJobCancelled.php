<?php

declare(strict_types=1);

namespace App\Domains\Listing\Listeners;

use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Listing\Actions\ReturnListingToActive;
use App\Domains\Listing\Models\Listing;

/**
 * Reaksi milik Listing atas event publik Inspection (JOB-3, perubahan shared).
 *
 * Aturan A2/A3: Inspection tidak memanggil Action Listing langsung; Listing
 * bereaksi pada event JobCancelled dengan memanggil Action miliknya sendiri
 * (ReturnListingToActive).
 */
final class ReturnListingToActiveOnJobCancelled
{
    public function __construct(private readonly ReturnListingToActive $returnToActive) {}

    public function handle(JobCancelled $event): void
    {
        $listing = Listing::query()->find($event->listingId);

        if (! $listing instanceof Listing) {
            return;
        }

        ($this->returnToActive)->handle($listing);
    }
}
