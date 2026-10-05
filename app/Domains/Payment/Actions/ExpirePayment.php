<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Exceptions\InvalidPaymentStatusTransitionException;
use App\Domains\Payment\Models\Payment;
use Illuminate\Support\Facades\DB;

final class ExpirePayment
{
    public function handle(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            /** @var Payment $fresh */
            $fresh = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status === PaymentStatus::Success || $fresh->status === PaymentStatus::Refunded) {
                throw InvalidPaymentStatusTransitionException::fromStatus(
                    $fresh->status,
                    'expired',
                );
            }

            if (! $fresh->isExpired()) {
                $fresh->update([
                    'expires_at' => now()->subSecond(),
                ]);
            }

            return $fresh->refresh();
        });
    }
}
