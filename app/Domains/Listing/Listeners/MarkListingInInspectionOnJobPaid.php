<?php

declare(strict_types=1);

namespace App\Domains\Listing\Listeners;

use App\Domains\Inspection\Events\JobPaid;
use App\Domains\Listing\Actions\MarkListingInInspection;
use App\Domains\Listing\Models\Listing;

/**
 * Reaksi milik Listing atas event publik Inspection (JOB-1, perubahan shared).
 *
 * Aturan A2/A3: Inspection tidak memanggil Action Listing langsung; Listing
 * bereaksi pada event JobPaid dengan memanggil Action miliknya sendiri.
 */
final class MarkListingInInspectionOnJobPaid
{
    public function __construct(private readonly MarkListingInInspection $markInInspection) {}

    public function handle(JobPaid $event): void
    {
        $listing = Listing::query()->find($event->listingId);

        if (! $listing instanceof Listing) {
            return;
        }

        ($this->markInInspection)->handle($listing);
    }
}
