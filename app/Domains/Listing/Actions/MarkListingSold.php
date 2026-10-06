<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Support\Facades\DB;

/**
 * Reaction target for the Transaction domain: a listing whose purchase
 * handover is confirmed moves to sold. Idempotent so repeated deliveries
 * of the same domain event stay harmless. Called by a listener owned by TRX.
 */
final class MarkListingSold
{
    public function handle(Listing $listing): Listing
    {
        if ($listing->status === ListingStatus::Sold) {
            return $listing->fresh();
        }

        if ($listing->status !== ListingStatus::InTransaction) {
            throw InvalidListingStatusTransitionException::fromStatus($listing->status, ListingStatus::Sold);
        }

        return DB::transaction(function () use ($listing): Listing {
            $affected = Listing::query()
                ->whereKey($listing->getKey())
                ->where('status', ListingStatus::InTransaction->value)
                ->update([
                    'status' => ListingStatus::Sold->value,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var Listing $fresh */
                $fresh = Listing::query()->findOrFail($listing->getKey());

                if ($fresh->status === ListingStatus::Sold) {
                    return $fresh;
                }

                throw InvalidListingStatusTransitionException::alreadyProcessed($fresh->status);
            }

            return $listing->refresh();
        });
    }
}
