<?php

declare(strict_types=1);

namespace App\Domains\Payment\DTOs;

use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Carbon\CarbonInterface;

final readonly class BillData
{
    public function __construct(
        public string $id,
        public string $payerId,
        public PaymentPurpose $purpose,
        public string $refId,
        public int $amount,
        public PaymentStatus $status,
        public ?PaymentMethod $method,
        public string $idempotencyKey,
        public ?CarbonInterface $expiresAt,
        public ?CarbonInterface $paidAt,
        public ?CarbonInterface $createdAt,
    ) {}

    public static function fromModel(Payment $payment): self
    {
        return new self(
            id: $payment->id,
            payerId: $payment->payer_id,
            purpose: $payment->purpose,
            refId: $payment->ref_id,
            amount: (int) $payment->amount,
            status: $payment->status,
            method: $payment->method,
            idempotencyKey: $payment->idempotency_key,
            expiresAt: $payment->expires_at,
            paidAt: $payment->paid_at,
            createdAt: $payment->created_at,
        );
    }
}
