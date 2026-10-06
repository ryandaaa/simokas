<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Listeners;

use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Events\JobExpired;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Models\Transaction;
use App\Support\Clock;

/**
 * Reaction to an inspection job reaching a final state (TRX-3, TRX-E5).
 *
 * While a job is active the purchase payment is not open yet (TRX-R5),
 * so the 24-hour payment countdown must start when the job finishes,
 * not when the offer was accepted. For accepted transactions on the
 * job's listing that have no payment bill yet, the expiry is restarted
 * to 24 hours from now. Conditional update keeps it idempotent and
 * race-safe; transactions that already opened a bill are untouched.
 */
final class RestartPaymentCountdownOnJobFinalized
{
    public function handle(JobCompleted|JobCancelled|JobExpired $event): void
    {
        $now = Clock::now();

        Transaction::query()
            ->where('listing_id', $event->listingId)
            ->where('status', TransactionStatus::Accepted->value)
            ->whereNull('payment_id')
            ->update([
                'expires_at' => $now->copy()->addHours(24),
                'updated_at' => $now,
            ]);
    }
}
