<?php

declare(strict_types=1);

namespace App\Domains\Setting\Exceptions;

use App\Support\Exceptions\DomainException;

final class InvalidSettingValueException extends DomainException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(string $message = 'Nilai pengaturan tidak valid.', array $context = [])
    {
        parent::__construct(
            message: $message,
            errorCode: DomainException::CODE_VALIDATION_ERROR,
            statusCode: 422,
            context: $context,
        );
    }
}
