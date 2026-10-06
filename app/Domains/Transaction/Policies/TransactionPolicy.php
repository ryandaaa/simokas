<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;

final class TransactionPolicy
{
    /**
     * Determine whether the user can view the transaction.
     * Buyer, seller, and active admin can view.
     */
    public function view(User $user, Transaction $transaction): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
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

    /**
     * Determine whether the user can cancel the transaction (TRX-3, D-04).
     * - offered: buyer only.
     * - accepted: buyer or seller.
     * - paid_held: seller or active admin (refund 100%).
     * - final statuses: nobody.
     */
    public function cancel(User $user, Transaction $transaction): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        $userId = (string) $user->id;
        $isBuyer = $userId === (string) $transaction->buyer_id;
        $isSeller = $userId === (string) $transaction->listing->seller_id;

        return match ($transaction->status) {
            TransactionStatus::Offered => $isBuyer,
            TransactionStatus::Accepted => $isBuyer || $isSeller,
            TransactionStatus::PaidHeld => $isSeller || $user->isAdmin(),
            default => false,
        };
    }

    /**
     * Determine whether the user can request cancellation of a paid_held
     * transaction (TRX-3, D-04). Only the buyer; the status stays paid_held.
     */
    public function requestCancellation(User $user, Transaction $transaction): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $transaction->status === TransactionStatus::PaidHeld
            && (string) $user->id === (string) $transaction->buyer_id;
    }

    /**
     * Determine whether the user can approve a pending cancellation
     * request (TRX-3, D-04). Only the seller, and only when the buyer
     * has actually requested.
     */
    public function approveCancellation(User $user, Transaction $transaction): bool
    {
        if ($user->isBlocked() || ! $user->isActive()) {
            return false;
        }

        return $transaction->status === TransactionStatus::PaidHeld
            && $transaction->cancel_requested_at !== null
            && (string) $user->id === (string) $transaction->listing->seller_id;
    }
}
