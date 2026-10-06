<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Exceptions\InvalidPaymentStatusTransitionException;
use App\Domains\Payment\Exceptions\PaymentAlreadyProcessedException;
use App\Domains\Payment\Exceptions\PaymentExpiredException;
use App\Domains\Payment\Models\Payment;
use Illuminate\Support\Facades\DB;

use function event;

final class ProcessPaymentResult
{
    public function handle(
        Payment $payment,
        PaymentMethod $method,
        PaymentSimulationOutcome $outcome,
        ?string $newIdempotencyKey = null,
    ): Payment {
        $result = DB::transaction(function () use ($payment, $method, $outcome, $newIdempotencyKey) {
            /** @var Payment $freshPayment */
            $freshPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($freshPayment->isExpired()) {
                throw new PaymentExpiredException;
            }

            $targetStatus = match ($outcome) {
                PaymentSimulationOutcome::Success => PaymentStatus::Success,
                PaymentSimulationOutcome::Failed => PaymentStatus::Failed,
                PaymentSimulationOutcome::Pending => PaymentStatus::Pending,
            };

            $currentStatus = $freshPayment->status;

            // Handle retry on failed payment: if a new idempotency key is provided, reset to pending first
            if ($currentStatus === PaymentStatus::Failed && $newIdempotencyKey !== null && $newIdempotencyKey !== '') {
                $affectedReset = Payment::query()
                    ->whereKey($freshPayment->id)
                    ->where('status', PaymentStatus::Failed->value)
                    ->update([
                        'status' => PaymentStatus::Pending->value,
                        'idempotency_key' => $newIdempotencyKey,
                        'method' => $method->value,
                    ]);

                if ($affectedReset === 0) {
                    throw PaymentAlreadyProcessedException::create($freshPayment->status);
                }

                $currentStatus = PaymentStatus::Pending;
            }

            // Check state machine transition validity
            if (! $currentStatus->canTransitionTo($targetStatus)) {
                throw InvalidPaymentStatusTransitionException::fromStatus($currentStatus, $targetStatus);
            }

            $updateData = [
                'status' => $targetStatus->value,
                'method' => $method->value,
                'paid_at' => $targetStatus === PaymentStatus::Success ? now() : null,
            ];

            // Conditional update: where status = $currentStatus
            $affected = Payment::query()
                ->whereKey($freshPayment->id)
                ->where('status', $currentStatus->value)
                ->update($updateData);

            if ($affected === 0) {
                throw PaymentAlreadyProcessedException::create($freshPayment->status);
            }

            $freshPayment->refresh();

            return [
                'payment' => $freshPayment,
                'succeeded' => $targetStatus === PaymentStatus::Success,
            ];
        });

        // Events are emitted only after the transaction commits — never inside it.
        if ($result['succeeded']) {
            /** @var Payment $succeededPayment */
            $succeededPayment = $result['payment'];

            event(new PaymentSucceeded(
                paymentId: $succeededPayment->id,
                purpose: $succeededPayment->purpose,
                refId: $succeededPayment->ref_id,
                amount: (int) $succeededPayment->amount,
                payerId: $succeededPayment->payer_id,
            ));
        }

        return $result['payment'];
    }
}
