<?php

declare(strict_types=1);

namespace App\Domains\Result\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultAccess;

final class ResultAccessPolicy
{
    /**
     * Check if user has full access to view complete result details.
     * Free access for: listing owner, inspector who created result, requester (job payer), admin.
     * Paid access via result_access record.
     * After listing is sold, only those with existing access can view.
     */
    public function viewDetails(User $user, InspectionResult $result): bool
    {
        // If listing is sold, only grant access to those with existing access record
        if ($result->listing->status === ListingStatus::Sold) {
            return ResultAccess::query()
                ->where('result_id', $result->getKey())
                ->where('user_id', $user->getKey())
                ->exists();
        }

        // Check free access (owner, inspector, requester, admin)
        if ($this->hasFreeAccess($user, $result)) {
            return true;
        }

        // Check paid access
        return ResultAccess::query()
            ->where('result_id', $result->getKey())
            ->where('user_id', $user->getKey())
            ->exists();
    }

    /**
     * Check if user has free access without payment.
     */
    public function hasFreeAccess(User $user, InspectionResult $result): bool
    {
        // Listing seller
        if ($result->listing->seller_id === $user->getKey()) {
            return true;
        }

        // Inspector who created the result
        if ($result->inspector_id === $user->getKey()) {
            return true;
        }

        // Requester (job payer / inspection requester)
        if ($result->job->requester_id === $user->getKey()) {
            return true;
        }

        // Admin
        if ($user->isAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can request paid access.
     * Cannot request if listing is sold or if already has access.
     */
    public function canRequestPaidAccess(User $user, InspectionResult $result): bool
    {
        // Cannot request if already has access
        if ($this->viewDetails($user, $result)) {
            return false;
        }

        // Cannot request if listing is sold
        if ($result->listing->status === ListingStatus::Sold) {
            return false;
        }

        return true;
    }
}
