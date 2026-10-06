<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionCancelled;
use App\Domains\Transaction\Exceptions\TransactionNotCancellableException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Cancel a transaction (TRX-3, D-04; ADM-4 extends admin rights).
 *
 * - offered: buyer cancels freely; active admin may cancel (ADM-4).
 * - accepted: buyer or seller cancels freely; active admin may cancel (ADM-4).
 * - paid_held: seller or active admin cancels; 100% refund via Ledger;
 *   reason required. Inspector commission is never posted (T3).
 * - Conditional update (UPDATE ... WHERE status = ...) so a stale model
 *   or a lost race (e.g. vs ConfirmHandover) applies exactly once.
 * - The event is dispatched exactly once, after commit, only on change.
 */
final class CancelTransaction
{
    public function __construct(private readonly Ledger $ledger) {}

    public function handle(User $actor, Transaction $transaction, ?string $reason = null): Transaction
    {
        $result = DB::transaction(function () use ($actor, $transaction, $reason): array {
            /** @var Transaction $fresh */
            $fresh = Transaction::query()->whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();

            $fromStatus = $fresh->status;

            // Idempotent: already cancelled (e.g. double click with a stale
            // model). No second event, no second refund.
            if ($fromStatus === TransactionStatus::Cancelled) {
                return ['transaction' => $fresh, 'changed' => false];
            }

            if (! in_array($fromStatus, [
                TransactionStatus::Offered,
                TransactionStatus::Accepted,
                TransactionStatus::PaidHeld,
            ], true)) {
                throw TransactionNotCancellableException::create();
            }

            $actorId = (string) $actor->getKey();
            $isBuyer = $actorId === (string) $fresh->buyer_id;
            $isSeller = $actorId === (string) $fresh->listing->seller_id;
            $isAdmin = Gate::forUser($actor)->allows('admin.access');

            $allowed = match ($fromStatus) {
                TransactionStatus::Offered => $isBuyer || $isAdmin,
                TransactionStatus::Accepted => $isBuyer || $isSeller || $isAdmin,
                TransactionStatus::PaidHeld => $isSeller || $isAdmin,
            };

            if (! $allowed) {
                throw UnauthorizedTransactionActionException::create();
            }

            $trimmedReason = $reason !== null ? trim($reason) : null;
            if ($fromStatus === TransactionStatus::PaidHeld && ($trimmedReason === null || $trimmedReason === '')) {
                throw TransactionNotCancellableException::reasonRequired();
            }

            $affected = Transaction::query()
                ->whereKey($fresh->getKey())
                ->where('status', $fromStatus->value)
                ->update([
                    'status' => TransactionStatus::Cancelled->value,
                    'cancelled_by' => $actorId,
                    'cancel_reason' => $trimmedReason !== '' ? $trimmedReason : null,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                /** @var Transaction $current */
                $current = Transaction::query()->findOrFail($fresh->getKey());

                // Idempotent: already cancelled by a concurrent actor.
                if ($current->status === TransactionStatus::Cancelled) {
                    return ['transaction' => $current, 'changed' => false];
                }

                throw TransactionNotCancellableException::create();
            }

            /** @var Transaction $cancelled */
            $cancelled = Transaction::query()->findOrFail($fresh->getKey());

            // paid_held: 100% refund to the buyer. No commission is ever
            // posted on cancel (T3); refundPayment is idempotent.
            if ($fromStatus === TransactionStatus::PaidHeld) {
                $this->ledger->refundPayment((string) $cancelled->payment_id);
            }

            return ['transaction' => $cancelled, 'changed' => true];
        });

        /** @var Transaction $transaction */
        $transaction = $result['transaction'];

        if ($result['changed']) {
            TransactionCancelled::dispatch(
                transactionId: (string) $transaction->getKey(),
                listingId: (string) $transaction->listing_id,
                buyerId: (string) $transaction->buyer_id,
                sellerId: (string) $transaction->listing->seller_id,
                cancelledBy: (string) $transaction->cancelled_by,
            );
        }

        return $transaction;
    }
}
