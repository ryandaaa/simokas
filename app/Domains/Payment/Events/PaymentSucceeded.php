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
        /**
         * Inspector earning the payout, when known by the dispatcher.
         *
         * ProcessPaymentResult leaves this null because neither Inspection nor
         * Result models exist yet at pay time. The access listener skips posting
         * while it is null; RES-4 posts explicitly through the Ledger contract
         * when granting access, passing the report author's id.
         */
        public readonly ?string $beneficiaryId = null,
    ) {}
}
