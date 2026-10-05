<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

enum PaymentSimulationOutcome: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::Success => __('payment.outcomes.success'),
            self::Failed => __('payment.outcomes.failed'),
            self::Pending => __('payment.outcomes.pending'),
        };
    }
}
