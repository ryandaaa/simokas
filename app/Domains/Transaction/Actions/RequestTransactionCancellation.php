<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Exceptions\TransactionNotCancellableException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;
use App\Support\Clock;

/**
 * Buyer requests cancellation of a paid_held transaction (TRX-3, D-04).
 *
 * The buyer cannot cancel directly; this only records the request
 * (cancel_requested_at). The status stays paid_held. Idempotent:
 * requesting twice keeps the first timestamp.
 */
final class RequestTransactionCancellation
{
    public function handle(User $buyer, Transaction $transaction): Transaction
    {
        /** @var Transaction $fresh */
        $fresh = Transaction::query()->findOrFail($transaction->getKey());

        if ($fresh->status !== TransactionStatus::PaidHeld) {
            throw TransactionNotCancellableException::create();
        }

        if ((string) $buyer->getKey() !== (string) $fresh->buyer_id) {
            throw UnauthorizedTransactionActionException::create();
        }

        if ($fresh->cancel_requested_at !== null) {
            return $fresh;
        }

        $now = Clock::now();

        Transaction::query()
            ->whereKey($fresh->getKey())
            ->where('status', TransactionStatus::PaidHeld->value)
            ->whereNull('cancel_requested_at')
            ->update([
                'cancel_requested_at' => $now,
                'updated_at' => $now,
            ]);

        /** @var Transaction $updated */
        $updated = Transaction::query()->findOrFail($fresh->getKey());

        return $updated;
    }
}
