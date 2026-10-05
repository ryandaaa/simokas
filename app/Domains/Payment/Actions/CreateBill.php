<?php

declare(strict_types=1);

namespace App\Domains\Payment\Actions;

use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class CreateBill
{
    public function handle(CreateBillData $data): Payment
    {
        $existing = Payment::query()
            ->where('idempotency_key', $data->idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $existing;
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
            // Race condition: concurrent request with the same idempotency_key
            $existing = Payment::query()
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            throw $e;
        }
    }
}
