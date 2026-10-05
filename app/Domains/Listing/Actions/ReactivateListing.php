<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingReactivated;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingModerationException;
use App\Domains\Listing\Exceptions\ListingValidationException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final class ReactivateListing
{
    public function __construct(private readonly Dispatcher $events) {}

    public function handle(User $actor, Listing $listing): Listing
    {
        if ($actor->isBlocked() || ! $actor->isActive()) {
            throw ListingValidationException::invalidSeller();
        }

        if (! $actor->isAdmin() && $actor->id !== $listing->seller_id) {
            throw ListingModerationException::forbidden();
        }

        if ($listing->status !== ListingStatus::Inactive) {
            throw InvalidListingStatusTransitionException::fromStatus($listing->status, ListingStatus::Active);
        }

        return DB::transaction(function () use ($actor, $listing): Listing {
            $affected = Listing::query()
                ->whereKey($listing->getKey())
                ->where('status', ListingStatus::Inactive->value)
                ->update([
                    'status' => ListingStatus::Active->value,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var Listing $fresh */
                $fresh = Listing::query()->findOrFail($listing->getKey());

                throw InvalidListingStatusTransitionException::alreadyProcessed($fresh->status);
            }

            $listing->refresh();

            $this->events->dispatch(new ListingReactivated(
                listingId: $listing->id,
                sellerId: $listing->seller_id,
                reactivatedBy: $actor->id,
            ));

            return $listing;
        });
    }
}
