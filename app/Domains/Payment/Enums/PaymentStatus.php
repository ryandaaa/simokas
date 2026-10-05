<?php

declare(strict_types=1);

namespace App\Domains\Payment\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Pending => in_array($to, [self::Success, self::Failed, self::Pending], true),
            self::Failed => $to === self::Pending,
            self::Success => $to === self::Refunded,
            self::Refunded => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('payment.status.pending'),
            self::Success => __('payment.status.success'),
            self::Failed => __('payment.status.failed'),
            self::Refunded => __('payment.status.refunded'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Success => 'success',
            self::Failed => 'danger',
            self::Refunded => 'muted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'jam',
            self::Success => 'centang',
            self::Failed => 'silang',
            self::Refunded => 'dua-panah',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isSuccess(): bool
    {
        return $this === self::Success;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

    public function isRefunded(): bool
    {
        return $this === self::Refunded;
    }
}
