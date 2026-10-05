<?php

declare(strict_types=1);

namespace App\Domains\Payment\DTOs;

use App\Domains\Payment\Enums\PaymentPurpose;
use Carbon\CarbonInterface;

final readonly class CreateBillData
{
    public function __construct(
        public string $payerId,
        public PaymentPurpose $purpose,
        public string $refId,
        public int $amount,
        public string $idempotencyKey,
        public ?CarbonInterface $expiresAt = null,
    ) {}
}
