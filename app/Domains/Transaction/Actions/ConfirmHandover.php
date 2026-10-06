<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionCompleted;
use App\Domains\Transaction\Exceptions\HandoverNotAvailableException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Action to confirm the handover and complete the transaction (TRX-2).
 *
 * - Only the buyer or an active admin.
 * - Conditional update (UPDATE ... WHERE status = 'paid_held') so a stale
 *   model or a concurrent confirm cannot double-apply (TRX-E13).
 * - The purchase commission is posted to the ledger in the same database
 *   transaction, from the snapshots taken at pay time; no ledger call when
 *   both commissions are zero.
 * - The event is dispatched exactly once, after commit, only on change.
 */
final class ConfirmHandover
{
    public function __construct(private readonly Ledger $ledger) {}

    public function handle(User $user, Transaction $transaction): Transaction
    {
        $isBuyer = (string) $user->getKey() === (string) $transaction->buyer_id;
        $isAdmin = Gate::forUser($user)->allows('admin.access');

        if (! $isBuyer && ! $isAdmin) {
            throw UnauthorizedTransactionActionException::create();
        }

        $changed = DB::transaction(function () use ($transaction): bool {
            $affected = Transaction::query()
                ->whereKey($transaction->getKey())
                ->where('status', TransactionStatus::PaidHeld->value)
                ->update([
                    'status' => TransactionStatus::Completed->value,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                return false;
            }

            /** @var Transaction $fresh */
            $fresh = Transaction::query()->findOrFail($transaction->getKey());

            $inspectorCommission = (int) ($fresh->inspector_commission ?? 0);
            $platformCommission = (int) ($fresh->platform_commission ?? 0);
            $saleCommission = $inspectorCommission + $platformCommission;

            if ($saleCommission > 0 || $inspectorCommission > 0) {
                $this->ledger->postPurchasePayout(
                    (string) $fresh->payment_id,
                    $saleCommission,
                    $inspectorCommission,
                    (string) ($fresh->inspector_id ?? ''),
                );
            }

            return true;
        });

        /** @var Transaction $fresh */
        $fresh = Transaction::query()->findOrFail($transaction->getKey());

        if ($changed) {
            TransactionCompleted::dispatch(
                transactionId: (string) $fresh->getKey(),
                listingId: (string) $fresh->listing_id,
                buyerId: (string) $fresh->buyer_id,
                sellerId: (string) $fresh->listing->seller_id,
            );

            return $fresh;
        }

        // Idempotent: already completed, return as-is without a second event or ledger posting.
        if ($fresh->status === TransactionStatus::Completed) {
            return $fresh;
        }

        throw HandoverNotAvailableException::create();
    }
}
