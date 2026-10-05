<?php

declare(strict_types=1);

namespace App\Domains\Result\Exceptions;

use App\Support\Exceptions\DomainException;

final class TemplateNotFoundException extends DomainException
{
    public static function create(string $vehicleType): self
    {
        return new self(
            "Template inspeksi aktif untuk jenis kendaraan {$vehicleType} tidak ditemukan.",
            'TEMPLATE_NOT_FOUND',
            404,
        );
    }
}
