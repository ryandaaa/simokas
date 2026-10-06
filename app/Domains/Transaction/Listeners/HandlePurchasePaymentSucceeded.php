<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Listeners;

use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Events\TransactionPaid;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Support\PurchaseBreakdown;
use Illuminate\Support\Facades\DB;

/**
 * Reaction to a successful purchase payment (TRX-2).
 *
 * - Ignores non-purchase payments and payments not opened for a transaction.
 * - Conditional update accepted -> paid_held with the commission snapshot
 *   (K2 via PurchaseBreakdown); the platform fee pct is snapshotted from
 *   the settings at pay time.
 * - A payment arriving after the transaction left the accepted status is
 *   late: refunded 100% to the buyer.
 * - Already paid_held/completed transactions are duplicate deliveries: no-op.
 * - Dispatches TransactionPaid exactly once, after commit, only on change.
 */
final class HandlePurchasePaymentSucceeded
{
    public function __construct(
        private readonly Ledger $ledger,
        private readonly SettingsReader $settings,
    ) {}

    public function handle(PaymentSucceeded $event): void
    {
        if ($event->purpose !== PaymentPurpose::Purchase) {
            return;
        }

        /** @var Transaction|null $transaction */
        $transaction = Transaction::query()->where('payment_id', $event->paymentId)->first();

        if ($transaction === null) {
            return;
        }

        $outcome = DB::transaction(function () use ($transaction): string {
            /** @var Transaction $fresh */
            $fresh = Transaction::query()->whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status === TransactionStatus::PaidHeld || $fresh->status === TransactionStatus::Completed) {
                return 'already';
            }

            if ($fresh->status !== TransactionStatus::Accepted) {
                return 'late';
            }

            $breakdown = $fresh->commission_pct === null
                ? ['commission' => 0, 'platform' => 0, 'inspector' => 0, 'seller' => (int) $fresh->price_amount]
                : PurchaseBreakdown::calculate(
                    (int) $fresh->price_amount,
                    (int) $fresh->commission_pct,
                    $this->settings->getPlatformFeePct(),
                );

            $affected = Transaction::query()
                ->whereKey($fresh->getKey())
                ->where('status', TransactionStatus::Accepted->value)
                ->update([
                    'status' => TransactionStatus::PaidHeld->value,
                    'inspector_commission' => $breakdown['inspector'],
                    'platform_commission' => $breakdown['platform'],
                    'updated_at' => now(),
                ]);

            return $affected === 1 ? 'paid' : 'already';
        });

        if ($outcome === 'late') {
            $this->ledger->refundPayment($event->paymentId);

            return;
        }

        if ($outcome === 'paid') {
            /** @var Transaction $fresh */
            $fresh = Transaction::query()->findOrFail($transaction->getKey());

            TransactionPaid::dispatch(
                transactionId: (string) $fresh->getKey(),
                listingId: (string) $fresh->listing_id,
                buyerId: (string) $fresh->buyer_id,
                sellerId: (string) $fresh->listing->seller_id,
                paymentId: (string) $fresh->payment_id,
            );
        }
    }
}
