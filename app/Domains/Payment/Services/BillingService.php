<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Actions\CreateBill;
use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\DTOs\BillData;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Models\Payment;

final class BillingService implements Billing
{
    public function __construct(
        private readonly CreateBill $createBillAction,
    ) {}

    public function createBill(CreateBillData $data): BillData
    {
        $payment = $this->createBillAction->handle($data);

        return BillData::fromModel($payment);
    }

    public function findBill(string $paymentId): ?BillData
    {
        $payment = Payment::query()->find($paymentId);

        return $payment !== null ? BillData::fromModel($payment) : null;
    }

    public function getBill(string $paymentId): BillData
    {
        $payment = Payment::query()->findOrFail($paymentId);

        return BillData::fromModel($payment);
    }
}
