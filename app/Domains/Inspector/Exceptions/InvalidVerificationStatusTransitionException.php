<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Exceptions;

use App\Domains\Inspector\Enums\VerificationStatus;
use App\Support\Exceptions\DomainException;

final class InvalidVerificationStatusTransitionException extends DomainException
{
    public static function fromStatus(VerificationStatus|string $from, VerificationStatus|string $to): self
    {
        $fromStr = $from instanceof VerificationStatus ? $from->value : (string) $from;
        $toStr = $to instanceof VerificationStatus ? $to->value : (string) $to;

        return new self(
            __('inspector.errors.invalid_transition', [
                'from' => $fromStr,
                'to' => $toStr,
            ]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['from' => $fromStr, 'to' => $toStr],
        );
    }

    public static function alreadyProcessed(VerificationStatus|string $currentStatus): self
    {
        $statusStr = $currentStatus instanceof VerificationStatus ? $currentStatus->value : (string) $currentStatus;

        return new self(
            __('inspector.errors.already_processed', ['status' => $statusStr]),
            self::CODE_CONFLICT,
            409,
            null,
            ['current_status' => $statusStr],
        );
    }

    public static function cannotResubmit(VerificationStatus|string $currentStatus): self
    {
        $statusStr = $currentStatus instanceof VerificationStatus ? $currentStatus->value : (string) $currentStatus;

        return new self(
            __('inspector.errors.cannot_resubmit', ['status' => $statusStr]),
            self::CODE_BUSINESS_RULE,
            422,
            null,
            ['current_status' => $statusStr],
        );
    }
}
