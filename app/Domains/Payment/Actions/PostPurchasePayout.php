<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Exceptions\InvalidLedgerPostingException;
use App\Domains\Payment\Exceptions\LedgerAlreadyPostedException;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Action for recording the purchase commission split (TRX-2).
 *
 * Called when the handover is confirmed (TRX-3). Amounts come fully from the
 * caller (snapshots on the transaction): no settings are read and no
 * Money::split is performed here. Only commissions are recorded; the
 * seller's share never enters the ledger.
 *
 * - Locks the payment row.
 * - Only success payments with purpose Purchase.
 * - Commissions non-negative, inspector share <= total, total <= payment amount.
 * - Inspector must exist and be an inspector when inspectorCommission > 0.
 * - Both commissions zero is a no-op (no entries written).
 * - Duplicate posting throws LedgerAlreadyPostedException.
 * - Never changes the payment status.
 */
final class PostPurchasePayout
{
    public function handle(Payment $payment, int $saleCommission, int $inspectorCommission, string $inspectorUserId): void
    {
        DB::transaction(function () use ($payment, $saleCommission, $inspectorCommission, $inspectorUserId) {
            /** @var Payment $fresh */
            $fresh = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->firstOrFail();

            if ($fresh->status !== PaymentStatus::Success) {
                throw InvalidLedgerPostingException::notSuccess($fresh->status);
            }

            if ($fresh->purpose !== PaymentPurpose::Purchase) {
                throw InvalidLedgerPostingException::purposeMismatch($fresh->purpose->value, LedgerEntryType::SaleCommission->value);
            }

            if ($saleCommission < 0) {
                throw InvalidLedgerPostingException::invalidCommission('sale', $saleCommission);
            }

            if ($inspectorCommission < 0) {
                throw InvalidLedgerPostingException::invalidCommission('inspector', $inspectorCommission);
            }

            if ($inspectorCommission > $saleCommission) {
                throw InvalidLedgerPostingException::inspectorExceedsCommission($inspectorCommission, $saleCommission);
            }

            if ($saleCommission > (int) $fresh->amount) {
                throw InvalidLedgerPostingException::commissionExceedsPayment($saleCommission, (int) $fresh->amount);
            }

            $inspectorId = null;

            if ($inspectorCommission > 0) {
                /** @var User|null $inspector */
                $inspector = User::query()->whereKey($inspectorUserId)->first();

                if ($inspector === null || ! $inspector->isInspector()) {
                    throw InvalidLedgerPostingException::invalidInspector($inspectorUserId);
                }

                $inspectorId = (string) $inspector->getKey();
            }

            if ($saleCommission === 0 && $inspectorCommission === 0) {
                return;
            }

            if (LedgerEntry::query()->where('payment_id', $fresh->getKey())->exists()) {
                throw LedgerAlreadyPostedException::forPayment((string) $fresh->getKey());
            }

            $now = now();

            $entries = [
                [
                    'id' => (string) Str::uuid(),
                    'payment_id' => $fresh->getKey(),
                    'account_user_id' => null,
                    'entry_type' => LedgerEntryType::PlatformFee->value,
                    'amount' => $saleCommission - $inspectorCommission,
                    'note' => __('payment.ledger.notes.purchase_platform_fee'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            if ($inspectorCommission > 0) {
                $entries[] = [
                    'id' => (string) Str::uuid(),
                    'payment_id' => $fresh->getKey(),
                    'account_user_id' => $inspectorId,
                    'entry_type' => LedgerEntryType::SaleCommission->value,
                    'amount' => $inspectorCommission,
                    'note' => __('payment.ledger.notes.purchase_payout'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Single statement so a mid-operation failure can never leave one entry behind.
            LedgerEntry::query()->insert($entries);
        });
    }
}
