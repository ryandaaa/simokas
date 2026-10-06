<?php

declare(strict_types=1);

namespace App\Domains\Result\Listeners;

use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Events\PaymentSucceeded;
use App\Domains\Payment\Models\Payment;
use App\Domains\Result\Actions\GrantResultAccess;
use App\Domains\Result\Models\InspectionResult;

final class GrantAccessOnPaymentSuccess
{
    public function __construct(private readonly GrantResultAccess $grantAccess) {}

    public function handle(PaymentSucceeded $event): void
    {
        // Only process access payments
        if ($event->purpose !== PaymentPurpose::Access) {
            return;
        }

        // refId should be result_id
        $result = InspectionResult::query()->find($event->refId);
        if ($result === null) {
            return;
        }

        // Get payment and payer
        $payment = Payment::query()->find($event->paymentId);
        if ($payment === null) {
            return;
        }

        $payer = $payment->payer;
        if ($payer === null) {
            return;
        }

        // Grant access and post ledger
        // Note: GrantResultAccess will internally call ledger->postAccessPayout
        $this->grantAccess->handle($result, $payer, $event->paymentId);
    }
}
