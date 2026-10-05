<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingSubmitted;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingModerationException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final class ResubmitListing
{
    public function __construct(private readonly Dispatcher $events) {}

    public function handle(User $seller, Listing $listing): Listing
    {
        if ($seller->id !== $listing->seller_id) {
            throw ListingModerationException::notOwner();
        }

        if (! $listing->status->canTransitionTo(ListingStatus::PendingReview)) {
            throw InvalidListingStatusTransitionException::cannotResubmit($listing->status);
        }

        return DB::transaction(function () use ($listing): Listing {
            $affected = Listing::query()
                ->whereKey($listing->getKey())
                ->where('status', ListingStatus::Rejected->value)
                ->update([
                    'status' => ListingStatus::PendingReview->value,
                    'rejection_reason' => null,
                    'validated_by' => null,
                    'validated_at' => null,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var Listing $fresh */
                $fresh = Listing::query()->findOrFail($listing->getKey());

                throw InvalidListingStatusTransitionException::cannotResubmit($fresh->status);
            }

            $listing->refresh();

            $this->events->dispatch(new ListingSubmitted(
                listingId: $listing->id,
                sellerId: $listing->seller_id,
            ));

            return $listing;
        });
    }
}
