<?php

declare(strict_types=1);

namespace App\Domains\Result\Exceptions;

use App\Support\Exceptions\DomainException;

final class ResultAlreadySubmittedException extends DomainException
{
    public static function create(): self
    {
        return new self(
            'Hasil inspeksi sudah dikirim dan tidak dapat diubah.',
            'RESULT_ALREADY_SUBMITTED',
            409,
        );
    }
}
