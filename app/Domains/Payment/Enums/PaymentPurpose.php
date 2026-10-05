<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

enum PaymentPurpose: string
{
    case Inspection = 'inspection';
    case Access = 'access';
    case Purchase = 'purchase';

    public function label(): string
    {
        return match ($this) {
            self::Inspection => __('payment.purpose.inspection'),
            self::Access => __('payment.purpose.access'),
            self::Purchase => __('payment.purpose.purchase'),
        };
    }
}
