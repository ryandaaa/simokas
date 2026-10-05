<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Actions\PostPayout;
use App\Domains\Payment\Actions\RefundPayment;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;

final class LedgerService implements Ledger
{
    public function __construct(
        private readonly PostPayout $payoutAction,
        private readonly RefundPayment $refundAction,
    ) {}

    public function postAccessPayout(string $paymentId, string $inspectorId): void
    {
        $this->payoutAction->handle($this->findPayment($paymentId), $inspectorId, LedgerEntryType::AccessFee);
    }

    public function postInspectionPayout(string $paymentId, string $inspectorId): void
    {
        $this->payoutAction->handle($this->findPayment($paymentId), $inspectorId, LedgerEntryType::InspectionFee);
    }

    public function refundPayment(string $paymentId): void
    {
        $this->refundAction->handle($this->findPayment($paymentId));
    }

    public function balanceFor(string $inspectorId): int
    {
        return (int) LedgerEntry::query()->where('account_user_id', $inspectorId)->sum('amount');
    }

    private function findPayment(string $paymentId): Payment
    {
        return Payment::query()->findOrFail($paymentId);
    }
}
