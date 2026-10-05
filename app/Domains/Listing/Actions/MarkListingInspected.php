<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Listing\Events\ListingInspected;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reaction target for the Inspection domain (used by LST-T14 in JOB-4):
 * records the inspection badge once a job completes. Idempotent: repeating
 * the call with the same timestamp leaves the same state.
 */
final class MarkListingInspected
{
    public function __construct(private readonly Dispatcher $events) {}

    public function handle(Listing $listing, ?Carbon $inspectedAt = null): Listing
    {
        $inspectedAt ??= now();

        return DB::transaction(function () use ($listing, $inspectedAt): Listing {
            Listing::query()
                ->whereKey($listing->getKey())
                ->update([
                    'is_inspected' => true,
                    'last_inspected_at' => $inspectedAt,
                    'updated_at' => now(),
                ]);

            $listing->refresh();

            $this->events->dispatch(new ListingInspected(
                listingId: $listing->id,
                sellerId: $listing->seller_id,
                inspectedAt: $listing->last_inspected_at->toIso8601String(),
            ));

            return $listing;
        });
    }
}
