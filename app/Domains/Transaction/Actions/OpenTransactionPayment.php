<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\DTOs\BillData;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Transaction\Enums\TransactionStatus;
use App\Domains\Transaction\Exceptions\ActiveJobBlocksPaymentException;
use App\Domains\Transaction\Exceptions\TransactionNotPayableException;
use App\Domains\Transaction\Exceptions\UnauthorizedTransactionActionException;
use App\Domains\Transaction\Models\Transaction;
use Illuminate\Support\Facades\DB;

/**
 * Action for the buyer to open the purchase payment bill (TRX-2).
 *
 * - Only the buyer, only from the accepted status.
 * - Blocked while an inspection job is active on the listing (TRX-R5, D-02).
 * - Idempotent: the stable idempotency key `purchase-transaction-{id}` makes
 *   double clicks return the same bill through the Billing contract.
 * - Stores the payment id on the transaction and (re)starts the 24-hour
 *   payment counter from when the payment is opened (D-04).
 */
final class OpenTransactionPayment
{
    public function __construct(private readonly Billing $billing) {}

    public function handle(User $buyer, Transaction $transaction): BillData
    {
        if ((string) $buyer->getKey() !== (string) $transaction->buyer_id) {
            throw UnauthorizedTransactionActionException::create();
        }

        return DB::transaction(function () use ($buyer, $transaction): BillData {
            /** @var Transaction $fresh */
            $fresh = Transaction::query()->whereKey($transaction->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== TransactionStatus::Accepted) {
                throw TransactionNotPayableException::create();
            }

            if (InspectionJob::query()->where('listing_id', $fresh->listing_id)->active()->exists()) {
                throw ActiveJobBlocksPaymentException::create();
            }

            $bill = $this->billing->createBill(new CreateBillData(
                payerId: (string) $buyer->getKey(),
                purpose: PaymentPurpose::Purchase,
                refId: (string) $fresh->getKey(),
                amount: (int) $fresh->price_amount,
                idempotencyKey: "purchase-transaction-{$fresh->getKey()}",
                expiresAt: $fresh->expires_at,
            ));

            // First open wins: link the payment and restart the 24-hour
            // counter from when the payment is opened (D-04).
            Transaction::query()
                ->whereKey($fresh->getKey())
                ->whereNull('payment_id')
                ->update([
                    'payment_id' => $bill->id,
                    'expires_at' => now()->addHours(24),
                    'updated_at' => now(),
                ]);

            return $bill;
        });
    }
}
