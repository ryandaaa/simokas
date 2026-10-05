<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingApproved;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingModerationException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final class ApproveListing
{
    public function __construct(private readonly Dispatcher $events) {}

    public function handle(User $admin, Listing $listing): Listing
    {
        if (! $admin->isAdmin()) {
            throw ListingModerationException::notAdmin();
        }

        if ($admin->id === $listing->seller_id) {
            throw ListingModerationException::selfApproval();
        }

        if ($listing->status !== ListingStatus::PendingReview) {
            throw InvalidListingStatusTransitionException::alreadyProcessed($listing->status);
        }

        return DB::transaction(function () use ($admin, $listing): Listing {
            $affected = Listing::query()
                ->whereKey($listing->getKey())
                ->where('status', ListingStatus::PendingReview->value)
                ->update([
                    'status' => ListingStatus::Active->value,
                    'rejection_reason' => null,
                    'validated_by' => $admin->id,
                    'validated_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var Listing $fresh */
                $fresh = Listing::query()->findOrFail($listing->getKey());

                throw InvalidListingStatusTransitionException::alreadyProcessed($fresh->status);
            }

            $listing->refresh();

            $this->events->dispatch(new ListingApproved(
                listingId: $listing->id,
                sellerId: $listing->seller_id,
                approvedBy: $admin->id,
            ));

            return $listing;
        });
    }
}
