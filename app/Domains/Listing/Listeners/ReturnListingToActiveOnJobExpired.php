<?php

declare(strict_types=1);

namespace App\Domains\Listing\Listeners;

use App\Domains\Inspection\Events\JobExpired;
use App\Domains\Listing\Actions\ReturnListingToActive;
use App\Domains\Listing\Models\Listing;

/**
 * Reaksi milik Listing atas event publik JobExpired dari Inspection (shared).
 */
final class ReturnListingToActiveOnJobExpired
{
    public function __construct(private readonly ReturnListingToActive $returnToActive) {}

    public function handle(JobExpired $event): void
    {
        $listing = Listing::query()->find($event->listingId);

        if (! $listing instanceof Listing) {
            return;
        }

        ($this->returnToActive)->handle($listing);
    }
}
