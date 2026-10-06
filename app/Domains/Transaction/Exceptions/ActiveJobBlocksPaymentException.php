<?php

declare(strict_types=1);

namespace App\Domains\Transaction\Exceptions;

use App\Support\Exceptions\DomainException;

final class ActiveJobBlocksPaymentException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('transaction.errors.active_job_blocks_payment'),
            'ACTIVE_JOB_BLOCKS_PAYMENT',
            422,
        );
    }
}
