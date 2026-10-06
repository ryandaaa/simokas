<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Exceptions\TransactionNotCancellableException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;

/**
 * Seller approves the buyer's cancellation request (TRX-3, D-04).
 *
 * The outcome is identical to a unilateral seller cancellation at
 * paid_held: 100% refund to the buyer. Delegates to CancelTransaction
 * so the refund and the event stay in one place.
 */
final class ApproveCancellationRequest
{
    public function __construct(private readonly CancelTransaction $cancelTransaction) {}

    public function handle(User $seller, Transaction $transaction, string $reason): Transaction
    {
        /** @var Transaction $fresh */
        $fresh = Transaction::query()->findOrFail($transaction->getKey());

        if ($fresh->status !== TransactionStatus::PaidHeld || $fresh->cancel_requested_at === null) {
            throw TransactionNotCancellableException::create();
        }

        if ((string) $seller->getKey() !== (string) $fresh->listing->seller_id) {
            throw UnauthorizedTransactionActionException::create();
        }

        return $this->cancelTransaction->handle($seller, $fresh, $reason);
    }
}
