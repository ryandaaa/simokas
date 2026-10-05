<?php

declare(strict_types=1);

namespace App\Domains\Listing\Listeners;

use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Listing\Actions\MarkListingInspected;
use App\Domains\Listing\Actions\ReturnListingToActive;
use App\Domains\Listing\Models\Listing;

/**
 * Reaksi milik Listing atas event publik JobCompleted dari Inspection (LST-T14, shared).
 */
final class MarkListingInspectedOnJobCompleted
{
    public function __construct(
        private readonly MarkListingInspected $markInspected,
        private readonly ReturnListingToActive $returnToActive,
    ) {}

    public function handle(JobCompleted $event): void
    {
        $listing = Listing::query()->find($event->listingId);

        if (! $listing instanceof Listing) {
            return;
        }

        $this->markInspected->handle($listing);
        $this->returnToActive->handle($listing);
    }
}
