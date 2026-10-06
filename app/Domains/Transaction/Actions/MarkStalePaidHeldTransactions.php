<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use App\Support\Clock;

/**
 * Flag stale paid_held transactions for admin attention (TRX-3, TRX-E10).
 *
 * A transaction is stale when its payment was made more than 7 days ago
 * and needs_attention_at is still empty. The status is never changed
 * here: no auto-complete, no auto-cancel. The Payment model is only
 * read, never written. Idempotent via the NULL guard.
 */
final class MarkStalePaidHeldTransactions
{
    public function handle(): int
    {
        $count = 0;
        $now = Clock::now();
        $threshold = (clone $now)->subDays(7);

        Transaction::query()
            ->where('status', TransactionStatus::PaidHeld->value)
            ->whereNull('needs_attention_at')
            ->whereHas('payment', function ($query) use ($threshold): void {
                $query->where('paid_at', '<=', $threshold);
            })
            ->chunkById(100, function ($transactions) use (&$count, $now): void {
                foreach ($transactions as $transaction) {
                    $affected = Transaction::query()
                        ->whereKey($transaction->getKey())
                        ->where('status', TransactionStatus::PaidHeld->value)
                        ->whereNull('needs_attention_at')
                        ->update([
                            'needs_attention_at' => $now,
                            'updated_at' => $now,
                        ]);

                    if ($affected === 1) {
                        $count++;
                    }
                }
            });

        return $count;
    }
}
