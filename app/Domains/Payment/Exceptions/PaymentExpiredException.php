<?php

declare(strict_types=1);

namespace App\Domains\Payment\Exceptions;

use App\Support\Exceptions\DomainException;

final class PaymentExpiredException extends DomainException
{
    public function __construct(
        string $message = '',
        array $context = [],
    ) {
        if ($message === '') {
            $message = __('payment.errors.payment_expired');
        }

        parent::__construct(
            $message,
            self::CODE_PAYMENT_EXPIRED,
            410,
            null,
            $context,
        );
    }
}
