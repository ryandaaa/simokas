<?php

declare(strict_types=1);

namespace App\Domains\Rating\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Rating\Models\Rating;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;

final class RatingPolicy
{
    public function view(?User $user, Rating $rating): bool
    {
        if (! $rating->is_hidden) {
            return true;
        }

        return $user !== null && $user->isAdmin() && $user->isActive() && ! $user->isBlocked();
    }

    public function create(User $user, Transaction $transaction): bool
    {
        if ((string) $transaction->buyer_id !== (string) $user->getKey()) {
            return false;
        }

        if ($transaction->status !== TransactionStatus::Completed) {
            return false;
        }

        if ($transaction->inspector_id === null) {
            return false;
        }

        return ! Rating::query()->where('transaction_id', $transaction->id)->exists();
    }

    /**
     * Rating cannot be edited once submitted (RTG-R3).
     */
    public function update(User $user, Rating $rating): bool
    {
        return false;
    }

    /**
     * Rating cannot be deleted by buyer (RTG-R3).
     */
    public function delete(User $user, Rating $rating): bool
    {
        return false;
    }

    public function hide(User $user, Rating $rating): bool
    {
        return $user->isAdmin() && $user->isActive() && ! $user->isBlocked();
    }

    public function unhide(User $user, Rating $rating): bool
    {
        return $user->isAdmin() && $user->isActive() && ! $user->isBlocked();
    }
}
