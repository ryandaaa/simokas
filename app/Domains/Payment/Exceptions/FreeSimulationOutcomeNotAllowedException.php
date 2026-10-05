<?php

declare(strict_types=1);

namespace App\Domains\Payment\Exceptions;

use App\Support\Exceptions\DomainException;

final class FreeSimulationOutcomeNotAllowedException extends DomainException
{
    public function __construct(
        string $message = '',
        array $context = [],
    ) {
        if ($message === '') {
            $message = __('payment.errors.free_outcome_not_allowed');
        }

        parent::__construct(
            $message,
            self::CODE_BUSINESS_RULE,
            422,
            null,
            $context,
        );
    }
}
