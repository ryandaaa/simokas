<?php

declare(strict_types=1);

namespace App\Domains\Payment\Exceptions;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Support\Exceptions\DomainException;

final class PaymentAlreadyProcessedException extends DomainException
{
    public static function create(PaymentStatus|string $status): self
    {
        $statusStr = $status instanceof PaymentStatus ? $status->value : (string) $status;

        return new self(
            __('payment.errors.already_processed'),
            self::CODE_CONFLICT,
            409,
            null,
            ['status' => $statusStr],
        );
    }
}
