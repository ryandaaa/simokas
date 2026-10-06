<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionExpired;
use App\Domains\Transaction\Models\Transaction;
use App\Support\Clock;
use Closure;
use Illuminate\Database\Query\Builder;

/**
 * Expire accepted transactions past their payment deadline (TRX-3, TRX-R9).
 *
 * The accepted expires_at runs 24 hours from when the payment opens
 * (D-04, TRX-E5): while an inspection job is active on the listing the
 * payment is not open yet, so those transactions are skipped. The
 * active-job check lives inside the conditional update itself (not only
 * in the initial query) so a job starting mid-run cannot lose the race.
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
            ->whereNotExists($this->activeJobExists())
            ->chunkById(100, function ($transactions) use (&$count, $now): void {
                foreach ($transactions as $transaction) {
                    $affected = Transaction::query()
                        ->whereKey($transaction->getKey())
                        ->where('status', TransactionStatus::Accepted->value)
                        ->where('expires_at', '<=', $now)
                        ->whereNotExists($this->activeJobExists())
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

    /**
     * Subquery: an active inspection job exists for the transaction's listing.
     * Mirrors InspectionJob::active() (same pattern as OpenTransactionPayment).
     *
     * @return Closure(Builder): void
     */
    private function activeJobExists(): Closure
    {
        return function ($query): void {
            $query->selectRaw('1')
                ->from('inspection_jobs')
                ->whereColumn('inspection_jobs.listing_id', 'transactions.listing_id')
                ->whereNotIn('inspection_jobs.status', ['completed', 'cancelled', 'expired']);
        };
    }
}
