<?php

declare(strict_types=1);

namespace App\Domains\Identity\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('identity.statuses.active'),
            self::Pending => __('identity.statuses.pending'),
            self::Blocked => __('identity.statuses.blocked'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'warning',
            self::Blocked => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Active => 'centang',
            self::Pending => 'jam',
            self::Blocked => 'silang',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isBlocked(): bool
    {
        return $this === self::Blocked;
    }
}
