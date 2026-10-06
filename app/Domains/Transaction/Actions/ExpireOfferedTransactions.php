<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionExpired;
use App\Domains\Transaction\Models\Transaction;
use App\Support\Clock;

/**
 * Expire offered transactions past their deadline (TRX-3, TRX-R9).
 *
 * Conditional updates (WHERE status = offered AND expires_at <= now) make
 * the run idempotent and safe against a concurrent manual accept/reject:
 * exactly one winner. The event fires only when the status truly changed.
 */
final class ExpireOfferedTransactions
{
    public function handle(): int
    {
        $count = 0;
        $now = Clock::now();

        Transaction::query()
            ->where('status', TransactionStatus::Offered->value)
            ->where('expires_at', '<=', $now)
            ->chunkById(100, function ($transactions) use (&$count, $now): void {
                foreach ($transactions as $transaction) {
                    $affected = Transaction::query()
                        ->whereKey($transaction->getKey())
                        ->where('status', TransactionStatus::Offered->value)
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
