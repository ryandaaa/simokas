<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

enum PaymentMethod: string
{
    case Qris = 'qris';
    case Transfer = 'transfer';
    case Ewallet = 'ewallet';

    public function label(): string
    {
        return match ($this) {
            self::Qris => __('payment.methods.qris'),
            self::Transfer => __('payment.methods.transfer'),
            self::Ewallet => __('payment.methods.ewallet'),
        };
    }
}
