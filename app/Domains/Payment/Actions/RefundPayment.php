<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Exceptions\PaymentAlreadyProcessedException;
use App\Domains\Payment\Exceptions\RefundNotAllowedException;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use Illuminate\Support\Facades\DB;

final class RefundPayment
{
    public function handle(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment $fresh */
            $fresh = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status !== PaymentStatus::Success && $fresh->status !== PaymentStatus::Refunded) {
                throw RefundNotAllowedException::notSuccess($fresh->status);
            }

            if ($fresh->status === PaymentStatus::Success
                && LedgerEntry::query()->where('payment_id', $fresh->id)->exists()
            ) {
                throw RefundNotAllowedException::alreadyPosted($fresh->id);
            }

            $affected = Payment::query()
                ->whereKey($fresh->id)
                ->where('status', PaymentStatus::Success->value)
                ->update(['status' => PaymentStatus::Refunded->value]);

            if ($affected === 0) {
                // Either an idempotent repeat or a lost race: re-read and answer accordingly.
                /** @var Payment $current */
                $current = Payment::query()->whereKey($fresh->id)->firstOrFail();

                $alreadyRefunded = $current->status === PaymentStatus::Refunded
                    && LedgerEntry::query()
                        ->where('payment_id', $current->id)
                        ->where('entry_type', LedgerEntryType::Refund->value)
                        ->exists();

                if ($alreadyRefunded) {
                    return $current;
                }

                throw PaymentAlreadyProcessedException::create($current->status);
            }

            LedgerEntry::query()->create([
                'payment_id' => $fresh->id,
                'account_user_id' => $fresh->payer_id,
                'entry_type' => LedgerEntryType::Refund,
                'amount' => (int) $fresh->amount,
                'note' => __('payment.ledger.notes.refund'),
            ]);

            return $fresh->refresh();
        });
    }
}
