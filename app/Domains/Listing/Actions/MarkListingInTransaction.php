<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Support\Facades\DB;

/**
 * Reaction target for the Transaction domain: a listing with a paid
 * purchase moves to in_transaction. Idempotent so repeated deliveries of
 * the same domain event stay harmless. Called by a listener owned by TRX.
 */
final class MarkListingInTransaction
{
    public function handle(Listing $listing): Listing
    {
        if ($listing->status === ListingStatus::InTransaction) {
            return $listing->fresh();
        }

        if (! in_array($listing->status, [ListingStatus::Active, ListingStatus::InInspection], true)) {
            throw InvalidListingStatusTransitionException::fromStatus($listing->status, ListingStatus::InTransaction);
        }

        return DB::transaction(function () use ($listing): Listing {
            $affected = Listing::query()
                ->whereKey($listing->getKey())
                ->whereIn('status', [ListingStatus::Active->value, ListingStatus::InInspection->value])
                ->update([
                    'status' => ListingStatus::InTransaction->value,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var Listing $fresh */
                $fresh = Listing::query()->findOrFail($listing->getKey());

                if ($fresh->status === ListingStatus::InTransaction) {
                    return $fresh;
                }

                throw InvalidListingStatusTransitionException::alreadyProcessed($fresh->status);
            }

            return $listing->refresh();
        });
    }
}
