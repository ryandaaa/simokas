<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Exceptions;

use App\Domains\Inspection\Enums\JobStatus;
use App\Support\Exceptions\DomainException;

final class InvalidJobStatusTransitionException extends DomainException
{
    public static function fromStatus(JobStatus|string $from, JobStatus|string $to): self
    {
        $fromStr = $from instanceof JobStatus ? $from->value : (string) $from;
        $toStr = $to instanceof JobStatus ? $to->value : (string) $to;

        return new self(
            __('inspection.errors.invalid_transition', ['from' => $fromStr, 'to' => $toStr]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['from' => $fromStr, 'to' => $toStr],
        );
    }

    public static function alreadyProcessed(JobStatus|string $currentStatus): self
    {
        $statusStr = $currentStatus instanceof JobStatus ? $currentStatus->value : (string) $currentStatus;

        return new self(
            __('inspection.errors.already_processed', ['status' => $statusStr]),
            self::CODE_CONFLICT,
            409,
            null,
            ['current_status' => $statusStr],
        );
    }
}
