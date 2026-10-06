<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionAccepted;
use App\Domains\Transaction\Exceptions\OfferNotPendingException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Action for seller to accept a purchase offer (TRX-1).
 *
 * Uses a conditional update (UPDATE ... WHERE status = 'offered') so a
 * stale model or a concurrent accept/reject cannot double-apply.
 * The event is dispatched exactly once, after the database transaction
 * commits, and only when the status truly changed.
 */
final class AcceptOffer
{
    public function handle(User $seller, Transaction $transaction): Transaction
    {
        if ((string) $seller->getKey() !== (string) $transaction->listing->seller_id) {
            throw UnauthorizedTransactionActionException::create();
        }

        $changed = DB::transaction(function () use ($transaction): bool {
            $affected = Transaction::query()
                ->whereKey($transaction->getKey())
                ->where('status', TransactionStatus::Offered->value)
                ->update([
                    'status' => TransactionStatus::Accepted->value,
                    'expires_at' => now()->addHours(24),
                    'updated_at' => now(),
                ]);

            return $affected === 1;
        });

        /** @var Transaction $fresh */
        $fresh = Transaction::query()->findOrFail($transaction->getKey());

        if ($changed) {
            TransactionAccepted::dispatch(
                transactionId: (string) $fresh->getKey(),
                listingId: (string) $fresh->listing_id,
                buyerId: (string) $fresh->buyer_id,
                sellerId: (string) $fresh->listing->seller_id,
            );

            return $fresh;
        }

        // Idempotent: already accepted by the winner, return as-is without a second event.
        if ($fresh->status === TransactionStatus::Accepted) {
            return $fresh;
        }

        throw OfferNotPendingException::create();
    }
}
