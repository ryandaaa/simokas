<?php

declare(strict_types=1);

namespace App\Domains\Payment\Listeners;

use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Events\PaymentSucceeded;

final class PostAccessLedger
{
    public function __construct(private readonly Ledger $ledger) {}

    public function handle(PaymentSucceeded $event): void
    {
        if ($event->purpose !== PaymentPurpose::Access) {
            return;
        }

        if ($event->beneficiaryId === null) {
            return;
        }

        $this->ledger->postAccessPayout($event->paymentId, $event->beneficiaryId);
    }
}
