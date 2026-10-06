<?php

declare(strict_types=1);

namespace App\Domains\Result\Enums;

enum ResultStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('inspection.form.status_draft'),
            self::Submitted => __('inspection.form.status_submitted'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Submitted => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'dokumen',
            self::Submitted => 'centang',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isSubmitted(): bool
    {
        return $this === self::Submitted;
    }

    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Draft => $to === self::Submitted,
            self::Submitted => $to === self::Draft,
        };
    }
}
