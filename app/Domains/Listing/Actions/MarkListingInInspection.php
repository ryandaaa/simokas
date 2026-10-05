<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Support\Facades\DB;

/**
 * Reaction target for the Inspection domain: an active listing moves to
 * in_inspection once a job is paid. Idempotent so repeated deliveries of
 * the same domain event stay harmless. Called by a listener owned by JOB.
 */
final class MarkListingInInspection
{
    public function handle(Listing $listing): Listing
    {
        if ($listing->status === ListingStatus::InInspection) {
            return $listing->fresh();
        }

        if ($listing->status !== ListingStatus::Active) {
            throw InvalidListingStatusTransitionException::fromStatus($listing->status, ListingStatus::InInspection);
        }

        return DB::transaction(function () use ($listing): Listing {
            $affected = Listing::query()
                ->whereKey($listing->getKey())
                ->where('status', ListingStatus::Active->value)
                ->update([
                    'status' => ListingStatus::InInspection->value,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var Listing $fresh */
                $fresh = Listing::query()->findOrFail($listing->getKey());

                if ($fresh->status === ListingStatus::InInspection) {
                    return $fresh;
                }

                throw InvalidListingStatusTransitionException::alreadyProcessed($fresh->status);
            }

            return $listing->refresh();
        });
    }
}
