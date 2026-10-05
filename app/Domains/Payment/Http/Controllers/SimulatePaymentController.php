<?php

declare(strict_types=1);

namespace App\Domains\Payment\Http\Controllers;

use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentSimulationOutcome;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Exceptions\FreeSimulationOutcomeNotAllowedException;
use App\Domains\Payment\Exceptions\PaymentExpiredException;
use App\Domains\Payment\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class SimulatePaymentController extends Controller
{
    public function __invoke(Request $request, ProcessPaymentResult $processPaymentResult): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'uuid'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'outcome' => ['nullable', Rule::enum(PaymentSimulationOutcome::class)],
            'idempotency_key' => ['nullable', 'string'],
        ]);

        /** @var Payment $payment */
        $payment = Payment::query()->findOrFail($validated['payment_id']);

        Gate::authorize('pay', $payment);

        if ($payment->isExpired()) {
            throw new PaymentExpiredException;
        }

        $isProduction = app()->isProduction() || config('app.env') === 'production';

        if ($isProduction) {
            if (isset($validated['outcome']) && $validated['outcome'] !== PaymentSimulationOutcome::Success->value) {
                throw new FreeSimulationOutcomeNotAllowedException;
            }
            $outcome = PaymentSimulationOutcome::Success;
        } else {
            $outcome = isset($validated['outcome'])
                ? PaymentSimulationOutcome::from($validated['outcome'])
                : PaymentSimulationOutcome::Success;
        }

        $method = PaymentMethod::from($validated['method']);
        $newKey = $payment->status === PaymentStatus::Failed ? ($validated['idempotency_key'] ?? null) : null;

        $updatedPayment = $processPaymentResult->handle(
            $payment,
            $method,
            $outcome,
            $newKey,
        );

        $jobStatus = match ($updatedPayment->status) {
            PaymentStatus::Success => 'paid',
            PaymentStatus::Failed => 'requested',
            PaymentStatus::Pending => 'requested',
            PaymentStatus::Refunded => 'cancelled',
        };

        return response()->json([
            'data' => [
                'payment_id' => $updatedPayment->id,
                'status' => $updatedPayment->status->value,
                'job_status' => $jobStatus,
            ],
            'error' => null,
        ]);
    }
}
