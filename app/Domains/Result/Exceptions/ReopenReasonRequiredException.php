<?php

declare(strict_types=1);

namespace App\Domains\Result\Exceptions;

use App\Support\Exceptions\DomainException;

final class ReopenReasonRequiredException extends DomainException
{
    public static function create(): self
    {
        return new self(
            'Alasan membuka kembali hasil inspeksi wajib diisi.',
            'REOPEN_REASON_REQUIRED',
            422,
        );
    }
}
