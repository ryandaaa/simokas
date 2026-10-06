<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use Illuminate\Support\Facades\DB;

/**
 * Reaction target for the Transaction domain: a listing whose purchase
 * was cancelled returns from in_transaction to active (TRX-3, D-04).
 *
 * Idempotent. Only acts when the listing is actually in_transaction;
 * any other status (e.g. a cancel at offered/accepted where the listing
 * never left active) is a harmless no-op.
 */
final class ReturnListingFromTransaction
{
    public function handle(Listing $listing): Listing
    {
        if ($listing->status !== ListingStatus::InTransaction) {
            return $listing->fresh();
        }

        return DB::transaction(function () use ($listing): Listing {
            $affected = Listing::query()
                ->whereKey($listing->getKey())
                ->where('status', ListingStatus::InTransaction->value)
                ->update([
                    'status' => ListingStatus::Active->value,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var Listing $fresh */
                $fresh = Listing::query()->findOrFail($listing->getKey());

                return $fresh;
            }

            return $listing->refresh();
        });
    }
}
