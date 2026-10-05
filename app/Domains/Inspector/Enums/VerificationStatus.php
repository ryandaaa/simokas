<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Enums;

enum VerificationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Pending => in_array($to, [self::Approved, self::Rejected], true),
            self::Rejected => $to === self::Pending,
            self::Approved => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('inspector.status.pending'),
            self::Approved => __('inspector.status.approved'),
            self::Rejected => __('inspector.status.rejected'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'jam',
            self::Approved => 'centang',
            self::Rejected => 'silang',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    public function isRejected(): bool
    {
        return $this === self::Rejected;
    }
}
