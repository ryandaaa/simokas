<?php

declare(strict_types=1);

namespace App\Domains\Payment\Events;

use App\Domains\Payment\Enums\PaymentPurpose;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PaymentSucceeded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $paymentId,
        public readonly PaymentPurpose $purpose,
        public readonly string $refId,
        public readonly int $amount,
        public readonly string $payerId,
    ) {}
}
