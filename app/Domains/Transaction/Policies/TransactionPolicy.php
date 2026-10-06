<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Models\Transaction;

final class TransactionPolicy
{
    /**
     * Determine whether the user can view the transaction.
     * Buyer and seller can view their own transaction.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        // Buyer or seller can view
        if ((string) $user->id === (string) $transaction->buyer_id) {
            return true;
        }

        // Get the listing to check if user is seller
        $listing = $transaction->listing;

        return (string) $user->id === (string) $listing->seller_id;
    }

    /**
     * Determine whether the user can accept an offer.
     * Only the seller (owner of the listing) can accept.
     */
    public function accept(User $user, Transaction $transaction): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        $listing = $transaction->listing;

        return (string) $user->id === (string) $listing->seller_id;
    }

    /**
     * Determine whether the user can reject an offer.
     * Only the seller (owner of the listing) can reject.
     */
    public function reject(User $user, Transaction $transaction): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        $listing = $transaction->listing;

        return (string) $user->id === (string) $listing->seller_id;
    }

    /**
     * Determine whether the user can open the purchase payment.
     * Only the buyer. The accepted status and the absence of an active
     * inspection job are enforced by the Action itself.
     */
    public function pay(User $user, Transaction $transaction): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return (string) $user->id === (string) $transaction->buyer_id;
    }

    /**
     * Determine whether the user can confirm the handover.
     * The buyer or an active admin.
     */
    public function confirmHandover(User $user, Transaction $transaction): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        if ((string) $user->id === (string) $transaction->buyer_id) {
            return true;
        }

        return $user->isAdmin();
    }
}
