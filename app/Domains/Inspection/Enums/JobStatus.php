<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Enums;

enum JobStatus: string
{
    case Requested = 'requested';
    case Paid = 'paid';
    case Taken = 'taken';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case ResultSubmitted = 'result_submitted';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /**
     * Sah bila sesuai diagram state machine di docs/flows/job.md bagian 1.
     */
    public function canTransitionTo(self $to): bool
    {
        return match ($this) {
            self::Requested => in_array($to, [self::Paid, self::Expired, self::Cancelled], true),
            self::Paid => in_array($to, [self::Taken, self::Cancelled], true),
            self::Taken => in_array($to, [self::Scheduled, self::Paid, self::Cancelled], true),
            self::Scheduled => in_array($to, [self::InProgress, self::Paid, self::Cancelled], true),
            self::InProgress => $to === self::ResultSubmitted,
            self::ResultSubmitted => $to === self::Completed,
            self::Completed, self::Cancelled, self::Expired => false,
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Expired], true);
    }

    public function isActive(): bool
    {
        return ! $this->isFinal();
    }

    public function label(): string
    {
        return __("inspection.status.{$this->value}");
    }

    public function color(): string
    {
        return match ($this) {
            self::Requested, self::ResultSubmitted => 'warning',
            self::Paid, self::Taken, self::Scheduled, self::InProgress => 'primary',
            self::Completed => 'success',
            self::Cancelled, self::Expired => 'muted',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Requested => 'jam',
            self::Paid => 'jam-pasir',
            self::Taken => 'orang',
            self::Scheduled => 'kalender',
            self::InProgress => 'kaca-pembesar',
            self::ResultSubmitted => 'dokumen',
            self::Completed => 'centang',
            self::Cancelled => 'silang',
            self::Expired => 'jam',
        };
    }
}
