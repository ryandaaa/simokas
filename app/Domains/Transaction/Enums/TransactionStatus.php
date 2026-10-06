<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Enums;

enum TransactionStatus: string
{
    case Offered = 'offered';
    case Accepted = 'accepted';
    case PaidHeld = 'paid_held';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /**
     * Check if a transition from current status to target status is valid.
     * Follows docs/flows/transaction.md state machine section 1.
     */
    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Offered => in_array($to, [self::Accepted, self::Rejected, self::Cancelled, self::Expired], true),
            self::Accepted => in_array($to, [self::PaidHeld, self::Cancelled, self::Expired], true),
            self::PaidHeld => in_array($to, [self::Completed, self::Cancelled], true),
            self::Completed, self::Rejected, self::Cancelled, self::Expired => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Rejected, self::Cancelled, self::Expired], true);
    }

    public function isActive(): bool
    {
        return ! $this->isFinal();
    }

    public function label(): string
    {
        return __("transaction.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Offered, self::Accepted => 'primary',
            self::PaidHeld => 'warning',
            self::Completed => 'success',
            self::Rejected, self::Cancelled, self::Expired => 'muted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Offered => 'jam',
            self::Accepted => 'centang',
            self::PaidHeld => 'jam-pasir',
            self::Completed => 'sukses',
            self::Rejected, self::Cancelled => 'silang',
            self::Expired => 'clock',
        };
    }
}
