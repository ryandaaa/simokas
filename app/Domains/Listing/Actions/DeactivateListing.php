<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingDeactivated;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingLockedException;
use App\Domains\Listing\Exceptions\ListingModerationException;
use App\Domains\Listing\Exceptions\ListingValidationException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final class DeactivateListing
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly ListingActivityChecker $activity,
    ) {}

    public function handle(User $actor, Listing $listing): Listing
    {
        if ($actor->isBlocked() || ! $actor->isActive()) {
            throw ListingValidationException::invalidSeller();
        }

        if (! $actor->isAdmin() && $actor->id !== $listing->seller_id) {
            throw ListingModerationException::forbidden();
        }

        if ($listing->status !== ListingStatus::Active) {
            throw InvalidListingStatusTransitionException::fromStatus($listing->status, ListingStatus::Inactive);
        }

        if ($this->activity->hasActiveJob($listing->id)
            || $this->activity->hasActiveTransaction($listing->id)) {
            throw ListingLockedException::deactivationBlocked();
        }

        return DB::transaction(function () use ($actor, $listing): Listing {
            $affected = Listing::query()
                ->whereKey($listing->getKey())
                ->where('status', ListingStatus::Active->value)
                ->update([
                    'status' => ListingStatus::Inactive->value,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var Listing $fresh */
                $fresh = Listing::query()->findOrFail($listing->getKey());

                throw InvalidListingStatusTransitionException::alreadyProcessed($fresh->status);
            }

            $listing->refresh();

            $this->events->dispatch(new ListingDeactivated(
                listingId: $listing->id,
                sellerId: $listing->seller_id,
                deactivatedBy: $actor->id,
            ));

            return $listing;
        });
    }
}
