<?php

declare(strict_types=1);

namespace App\Domains\Identity\Enums;

enum UserRole: string
{
    case User = 'user';
    case Inspector = 'inspector';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User => __('identity.roles.user'),
            self::Inspector => __('identity.roles.inspector'),
            self::Admin => __('identity.roles.admin'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::User => 'primary',
            self::Inspector => 'warning',
            self::Admin => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::User => 'orang',
            self::Inspector => 'kaca-pembesar',
            self::Admin => 'perisai-centang',
        };
    }

    public function isUser(): bool
    {
        return $this === self::User;
    }

    public function isInspector(): bool
    {
        return $this === self::Inspector;
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * D-06: Inspektor dilarang membeli, menjual, dan mengajukan inspeksi.
     */
    public function canBuy(): bool
    {
        return ! $this->isInspector();
    }

    public function canSell(): bool
    {
        return ! $this->isInspector();
    }

    public function canRequestInspection(): bool
    {
        return ! $this->isInspector();
    }
}
