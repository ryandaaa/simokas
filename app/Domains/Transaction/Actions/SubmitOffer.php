<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Exceptions\InspectorCannotBuyException;
use App\Domains\Transaction\Exceptions\InvalidListingStatusException;
use App\Domains\Transaction\Exceptions\OwnListingOfferException;
use App\Domains\Transaction\Exceptions\TransactionAlreadyRunningException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Action to submit a purchase offer for a listing (TRX-1).
 *
 * - Only active or in_inspection listings can be offered (TRX-R1)
 * - Buyer cannot be the seller (TRX-E1)
 * - Inspector cannot buy (D-06)
 * - Only one transaction per listing (TRX-R2)
 * - Price, commission pct, and inspector are copied at time of offer (TRX-R3)
 */
final class SubmitOffer
{
    public function handle(User $buyer, Listing $listing, int $priceAmount, ?string $notes = null): Transaction
    {
        return DB::transaction(function () use ($buyer, $listing, $priceAmount, $notes): Transaction {
            // TRX-R1: Only active or in_inspection listings
            if (! in_array($listing->status, [ListingStatus::Active, ListingStatus::InInspection], true)) {
                throw InvalidListingStatusException::create();
            }

            // TRX-E1: Cannot buy own listing
            if ((string) $buyer->id === (string) $listing->seller_id) {
                throw OwnListingOfferException::create();
            }

            // D-06: Inspector cannot buy
            if ($buyer->isInspector()) {
                throw InspectorCannotBuyException::create();
            }

            // TRX-R2: Only one active transaction per listing
            $existingTransaction = Transaction::query()
                ->forListing($listing->id)
                ->active()
                ->first();

            if ($existingTransaction !== null) {
                throw TransactionAlreadyRunningException::create();
            }

            // TRX-R3: Copy price, commission percent, and inspector
            // Commission is 3% if listing has a completed inspection result
            $commissionPct = null;
            $inspectorId = null;

            if ($listing->is_inspected) {
                $latestResult = InspectionResult::query()
                    ->where('listing_id', $listing->id)
                    ->orderBy('submitted_at', 'desc')
                    ->first();

                if ($latestResult !== null) {
                    $commissionPct = 3; // Default from flow section 3
                    $inspectorId = $latestResult->inspector_id;
                }
            }

            return Transaction::create([
                'listing_id' => $listing->id,
                'buyer_id' => $buyer->id,
                'status' => TransactionStatus::Offered,
                'price_amount' => $priceAmount,
                'commission_pct' => $commissionPct,
                'inspector_id' => $inspectorId,
                'notes' => $notes,
                'expires_at' => now()->addHours(48),
            ]);
        });
    }
}
