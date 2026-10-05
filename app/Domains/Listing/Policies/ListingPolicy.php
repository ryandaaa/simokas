<?php

declare(strict_types=1);

namespace App\Domains\Listing\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingPhoto;

final class ListingPolicy
{
    /**
     * Determine whether the user can create listings.
     * Only users with role 'user' who are active and not blocked can create listings.
     * Inspectors (D-06) and Admins cannot create listings.
     */
    public function create(User $user): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        if ($user->isInspector() || $user->isAdmin()) {
            return false;
        }

        return $user->isUser();
    }

    /**
     * Determine whether the user can view the listing (public or private).
     */
    public function view(?User $user, Listing $listing): bool
    {
        if ($listing->status === ListingStatus::Active) {
            return true;
        }

        if ($user === null || $user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $user->isAdmin() || $user->id === $listing->seller_id;
    }

    /**
     * Determine whether the user can view the private details of a listing.
     * (e.g. lat, lng, address_full, chassis_number).
     */
    public function viewPrivate(User $user, Listing $listing): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $user->isAdmin() || $user->id === $listing->seller_id;
    }

    /**
     * Determine whether the user can update the listing.
     * Only owner and admin can edit; other roles 403.
     */
    public function update(User $user, Listing $listing): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $listing->seller_id;
    }

    /**
     * Determine whether the user can resubmit a rejected listing for review.
     */
    public function resubmit(User $user, Listing $listing): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $user->id === $listing->seller_id
            && $listing->status === ListingStatus::Rejected;
    }

    /**
     * Determine whether the user can view a photo of the listing.
     * Photos of photo-public listings (active, in_inspection,
     * in_transaction, sold [USULAN]) may be opened by anyone, including
     * guests, so the public etalase and detail pages never show broken
     * images (CAT-R1, CAT-E5, LST-E11). Photos of pending_review, rejected,
     * and inactive listings stay restricted to the owner and admins.
     * Blocked and non-active users are always denied.
     */
    public function viewPhoto(?User $user, Listing $listing, ListingPhoto $photo): bool
    {
        if ($user !== null && ($user->isBlocked() || ! $user->isActive())) {
            return false;
        }

        if ($listing->status->isPhotoPubliclyVisible()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->isAdmin() || $user->id === $listing->seller_id;
    }
}
