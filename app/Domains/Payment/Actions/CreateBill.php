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
