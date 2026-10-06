<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionOffered;
use App\Domains\Transaction\Exceptions\InspectorCannotBuyException;
use App\Domains\Transaction\Exceptions\InvalidListingStatusException;
use App\Domains\Transaction\Exceptions\InvalidOfferPriceException;
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
 * - Commission pct comes from Setting (snapshot), never hardcoded
 * - Only a submitted inspection result counts for commission; a draft
 *   (re-inspection in progress) must never attach commission to an
 *   unfinished inspector
 */
final class SubmitOffer
{
    public function __construct(
        private readonly SettingsReader $settings,
    ) {}

    public function handle(User $buyer, Listing $listing, int $priceAmount, ?string $notes = null): Transaction
    {
        if ($priceAmount <= 0) {
            throw InvalidOfferPriceException::create();
        }

        $transaction = DB::transaction(function () use ($buyer, $listing, $priceAmount, $notes): Transaction {
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

            // TRX-R3: Copy price, commission percent, and inspector.
            // Only a submitted inspection result counts; a draft result
            // (re-inspection being filled) must not attach commission.
            $commissionPct = null;
            $inspectorId = null;

            if ($listing->is_inspected) {
                $latestResult = InspectionResult::query()
                    ->where('listing_id', $listing->id)
                    ->where('status', ResultStatus::Submitted->value)
                    ->orderBy('submitted_at', 'desc')
                    ->first();

                if ($latestResult !== null) {
                    $commissionPct = $this->settings->getSaleCommissionPct();
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

        TransactionOffered::dispatch(
            transactionId: (string) $transaction->getKey(),
            listingId: (string) $listing->getKey(),
            buyerId: (string) $buyer->getKey(),
            sellerId: (string) $listing->seller_id,
        );

        return $transaction;
    }
}
