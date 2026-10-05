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
}
