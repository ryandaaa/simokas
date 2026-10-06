<?php

declare(strict_types=1);

namespace App\Domains\Listing\Actions;

use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;

/**
 * Deactivate all active listings of a user (ADM-4, LST-T15).
 *
 * Only listings in the active status move to inactive, following
 * ListingStatus::canTransitionTo (active -> inactive is allowed).
 * Conditional bulk update: idempotent, safe to run twice.
 */
final class DeactivateListingsOfUser
{
    public function handle(string $userId): int
    {
        return Listing::query()
            ->where('seller_id', $userId)
            ->where('status', ListingStatus::Active->value)
            ->update([
                'status' => ListingStatus::Inactive->value,
                'updated_at' => now(),
            ]);
    }
}
