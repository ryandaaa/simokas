<?php

declare(strict_types=1);

namespace App\Domains\Payment\Exceptions;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Support\Exceptions\DomainException;

final class InvalidLedgerPostingException extends DomainException
{
    public static function notSuccess(PaymentStatus|string $status): self
    {
        $statusStr = $status instanceof PaymentStatus ? $status->value : (string) $status;

        return new self(
            __('payment.errors.posting_not_success', ['status' => $statusStr]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['status' => $statusStr],
        );
    }

    public static function purposeMismatch(string $purpose, string $entryType): self
    {
        return new self(
            __('payment.errors.posting_purpose_mismatch', ['purpose' => $purpose, 'type' => $entryType]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['purpose' => $purpose, 'entry_type' => $entryType],
        );
    }

    public static function invalidInspector(string $inspectorId): self
    {
        return new self(
            __('payment.errors.posting_invalid_inspector'),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['inspector_id' => $inspectorId],
        );
    }

    public static function invalidPercentage(int $pct): self
    {
        return new self(
            __('payment.errors.posting_invalid_percentage', ['pct' => $pct]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['platform_fee_pct' => $pct],
        );
    }

    public static function snapshotRequired(): self
    {
        return new self(
            __('payment.errors.posting_snapshot_required'),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            [],
        );
    }

    public static function invalidCommission(string $which, int $amount): self
    {
        return new self(
            __('payment.errors.posting_invalid_commission', ['which' => $which, 'amount' => $amount]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['commission' => $which, 'amount' => $amount],
        );
    }

    public static function inspectorExceedsCommission(int $inspectorCommission, int $saleCommission): self
    {
        return new self(
            __('payment.errors.posting_inspector_exceeds_commission', [
                'inspector' => $inspectorCommission,
                'total' => $saleCommission,
            ]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['inspector_commission' => $inspectorCommission, 'sale_commission' => $saleCommission],
        );
    }

    public static function commissionExceedsPayment(int $saleCommission, int $paymentAmount): self
    {
        return new self(
            __('payment.errors.posting_commission_exceeds_payment', [
                'total' => $saleCommission,
                'amount' => $paymentAmount,
            ]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['sale_commission' => $saleCommission, 'payment_amount' => $paymentAmount],
        );
    }
}
