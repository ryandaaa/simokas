<?php

declare(strict_types=1);

namespace App\Domains\Payment\Exceptions;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Support\Exceptions\DomainException;

final class InvalidPaymentStatusTransitionException extends DomainException
{
    public static function fromStatus(PaymentStatus|string $from, PaymentStatus|string $to): self
    {
        $fromStr = $from instanceof PaymentStatus ? $from->value : (string) $from;
        $toStr = $to instanceof PaymentStatus ? $to->value : (string) $to;

        return new self(
            __('payment.errors.invalid_transition', [
                'from' => $fromStr,
                'to' => $toStr,
            ]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['from' => $fromStr, 'to' => $toStr],
        );
    }
}
