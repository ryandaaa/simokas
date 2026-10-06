<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionExpired;
use App\Domains\Transaction\Models\Transaction;
use App\Support\Clock;

/**
 * Expire accepted transactions past their payment deadline (TRX-3, TRX-R9).
 *
 * The accepted expires_at is set 24 hours after the payment bill opens
 * (OpenTransactionPayment); this job only flips what is overdue.
 * Same idempotency and exactly-once event guarantees as offered expiry.
 */
final class ExpireAcceptedTransactions
{
    public function handle(): int
    {
        $count = 0;
        $now = Clock::now();

        Transaction::query()
            ->where('status', TransactionStatus::Accepted->value)
            ->where('expires_at', '<=', $now)
            ->chunkById(100, function ($transactions) use (&$count, $now): void {
                foreach ($transactions as $transaction) {
                    $affected = Transaction::query()
                        ->whereKey($transaction->getKey())
                        ->where('status', TransactionStatus::Accepted->value)
                        ->where('expires_at', '<=', $now)
                        ->update([
                            'status' => TransactionStatus::Expired->value,
                            'updated_at' => $now,
                        ]);

                    if ($affected !== 1) {
                        continue;
                    }

                    $count++;

                    /** @var Transaction $fresh */
                    $fresh = Transaction::query()->findOrFail($transaction->getKey());

                    TransactionExpired::dispatch(
                        transactionId: (string) $fresh->getKey(),
                        listingId: (string) $fresh->listing_id,
                        buyerId: (string) $fresh->buyer_id,
                        sellerId: (string) $fresh->listing->seller_id,
                    );
                }
            });

        return $count;
    }
}
