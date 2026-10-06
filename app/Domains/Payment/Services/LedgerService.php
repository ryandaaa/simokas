<?php

declare(strict_types=1);

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Actions\PostPayout;
use App\Domains\Payment\Actions\PostPurchasePayout;
use App\Domains\Payment\Actions\RefundPayment;
use App\Domains\Payment\Contracts\Ledger;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;

final class LedgerService implements Ledger
{
    public function __construct(
        private readonly PostPayout $payoutAction,
        private readonly PostPurchasePayout $purchasePayoutAction,
        private readonly RefundPayment $refundAction,
    ) {}

    public function postAccessPayout(string $paymentId, string $inspectorId, ?int $platformFeePct = null): void
    {
        $this->payoutAction->handle($this->findPayment($paymentId), $inspectorId, LedgerEntryType::AccessFee, $platformFeePct);
    }

    public function postInspectionPayout(string $paymentId, string $inspectorId, int $platformFeePct): void
    {
        $this->payoutAction->handle($this->findPayment($paymentId), $inspectorId, LedgerEntryType::InspectionFee, $platformFeePct);
    }

    public function postPurchasePayout(string $paymentId, int $saleCommission, int $inspectorCommission, string $inspectorUserId): void
    {
        $this->purchasePayoutAction->handle($this->findPayment($paymentId), $saleCommission, $inspectorCommission, $inspectorUserId);
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
