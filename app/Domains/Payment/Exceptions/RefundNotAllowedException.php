<?php

declare(strict_types=1);

namespace App\Domains\Payment\Exceptions;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Support\Exceptions\DomainException;

final class RefundNotAllowedException extends DomainException
{
    public static function notSuccess(PaymentStatus|string $status): self
    {
        $statusStr = $status instanceof PaymentStatus ? $status->value : (string) $status;

        return new self(
            __('payment.errors.refund_not_allowed', ['status' => $statusStr]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['status' => $statusStr],
        );
    }

    public static function alreadyPosted(string $paymentId): self
    {
        return new self(
            __('payment.errors.refund_already_posted'),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['payment_id' => $paymentId],
        );
    }
}
