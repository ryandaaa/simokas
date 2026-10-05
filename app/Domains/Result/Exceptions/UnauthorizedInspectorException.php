<?php

declare(strict_types=1);

namespace App\Domains\Result\Exceptions;

use App\Support\Exceptions\DomainException;

final class UnauthorizedInspectorException extends DomainException
{
    public static function create(): self
    {
        return new self(
            __('inspection.form.unauthorized'),
            'UNAUTHORIZED_INSPECTOR',
            403,
        );
    }
}
