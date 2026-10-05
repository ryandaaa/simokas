<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Exceptions\IdempotencyKeyConflictException;
use App\Domains\Payment\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class CreateBill
{
    public function handle(CreateBillData $data): Payment
    {
        // Same key as before: return the same bill, or reject on mismatch.
        $byKey = Payment::query()->where('idempotency_key', $data->idempotencyKey)->first();

        if ($byKey !== null) {
            $this->ensureIdempotencyMatch($byKey, $data);

            return $byKey;
        }

        // PAY-T15: reopening the same access keeps using the existing bill
        // instead of creating a second one.
        $reusable = $this->findReusableBill($data);

        if ($reusable !== null) {
            return $reusable;
        }

        try {
            return DB::transaction(function () use ($data) {
                return Payment::create([
                    'payer_id' => $data->payerId,
                    'purpose' => $data->purpose,
                    'ref_id' => $data->refId,
                    'amount' => $data->amount,
                    'status' => PaymentStatus::Pending,
                    'method' => null,
                    'idempotency_key' => $data->idempotencyKey,
                    'expires_at' => $data->expiresAt ?? now()->addMinutes(30),
                    'paid_at' => null,
                ]);
            });
        } catch (QueryException $e) {
            // Key already used (including concurrent races): return the same
            // bill when parameters match, reject when they differ.
            $existing = Payment::query()
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existing === null) {
                throw $e;
            }

            $this->ensureIdempotencyMatch($existing, $data);

            return $existing;
        }
    }

    private function findReusableBill(CreateBillData $data): ?Payment
    {
        /** @var Payment|null $candidate */
        $candidate = Payment::query()
            ->where('payer_id', $data->payerId)
            ->where('purpose', $data->purpose->value)
            ->where('ref_id', $data->refId)
            ->whereIn('status', [
                PaymentStatus::Pending->value,
                PaymentStatus::Failed->value,
                PaymentStatus::Success->value,
            ])
            ->orderByRaw(
                'CASE WHEN status = ? THEN 0 ELSE 1 END',
                [PaymentStatus::Success->value]
            )
            ->latest()
            ->first();

        if ($candidate === null) {
            return null;
        }

        // A paid bill is always reused so the payer is never charged twice.
        if ($candidate->status === PaymentStatus::Success) {
            return $candidate;
        }

        return $candidate->isExpired() ? null : $candidate;
    }

    private function ensureIdempotencyMatch(Payment $existing, CreateBillData $data): void
    {
        $matches = $existing->payer_id === $data->payerId
            && $existing->purpose === $data->purpose
            && $existing->ref_id === $data->refId
            && (int) $existing->amount === $data->amount;

        if (! $matches) {
            throw new IdempotencyKeyConflictException;
        }
    }
}
