<?php

declare(strict_types=1);

namespace App\Domains\Payment\Exceptions;

use App\Support\Exceptions\DomainException;

final class IdempotencyKeyConflictException extends DomainException
{
    public function __construct()
    {
        parent::__construct(
            __('payment.errors.idempotency_key_conflict'),
            'IDEMPOTENCY_KEY_CONFLICT',
            409,
        );
    }
}
