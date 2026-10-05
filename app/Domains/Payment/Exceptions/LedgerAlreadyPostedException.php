<?php

declare(strict_types=1);

namespace App\Domains\Payment\Exceptions;

use App\Support\Exceptions\DomainException;

final class LedgerAlreadyPostedException extends DomainException
{
    public static function forPayment(string $paymentId): self
    {
        return new self(
            __('payment.errors.already_posted'),
            self::CODE_CONFLICT,
            409,
            null,
            ['payment_id' => $paymentId],
        );
    }
}
