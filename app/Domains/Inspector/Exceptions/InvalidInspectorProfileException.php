<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Exceptions;

use App\Support\Exceptions\DomainException;

final class InvalidInspectorProfileException extends DomainException
{
    public static function notAnInspector(): self
    {
        return new self(
            message: 'Hanya pengguna dengan peran inspektor yang dapat memiliki profil inspektor.',
            errorCode: DomainException::CODE_NOT_ALLOWED,
            statusCode: 403,
        );
    }

    public static function invalidVehicleTypes(): self
    {
        return new self(
            message: 'Pilih minimal satu jenis keahlian kendaraan yang valid.',
            errorCode: DomainException::CODE_VALIDATION_ERROR,
            statusCode: 422,
        );
    }

    public static function invalidExperienceYears(): self
    {
        return new self(
            message: 'Pengalaman harus bernilai antara 0 hingga 50 tahun.',
            errorCode: DomainException::CODE_VALIDATION_ERROR,
            statusCode: 422,
        );
    }
}
