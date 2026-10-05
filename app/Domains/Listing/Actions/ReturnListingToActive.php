<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Support\Facades\DB;

/**
 * Reaction target for the Inspection domain: a listing returns to active
 * once its job finishes, is cancelled, or expires. Idempotent. Called by
 * a listener owned by JOB.
 */
final class ReturnListingToActive
{
    public function handle(Listing $listing): Listing
    {
        if ($listing->status === ListingStatus::Active) {
            return $listing->fresh();
        }

        if ($listing->status !== ListingStatus::InInspection) {
            throw InvalidListingStatusTransitionException::fromStatus($listing->status, ListingStatus::Active);
        }

        return DB::transaction(function () use ($listing): Listing {
            $affected = Listing::query()
                ->whereKey($listing->getKey())
                ->where('status', ListingStatus::InInspection->value)
                ->update([
                    'status' => ListingStatus::Active->value,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var Listing $fresh */
                $fresh = Listing::query()->findOrFail($listing->getKey());

                if ($fresh->status === ListingStatus::Active) {
                    return $fresh;
                }

                throw InvalidListingStatusTransitionException::alreadyProcessed($fresh->status);
            }

            return $listing->refresh();
        });
    }
}
