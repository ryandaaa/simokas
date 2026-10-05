<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Exceptions;

use App\Support\Exceptions\DomainException;

final class DocumentUploadException extends DomainException
{
    public static function fileTooLarge(int $maxSizeBytes = 2097152): self
    {
        return new self(
            message: 'Ukuran berkas melebihi batas maksimal 2 MB.',
            errorCode: 'FILE_TOO_LARGE',
            statusCode: 413,
            context: ['max_size_bytes' => $maxSizeBytes],
        );
    }

    public static function unsupportedType(string $detectedMime): self
    {
        return new self(
            message: 'Tipe atau format berkas tidak didukung atau isi berkas tidak sesuai.',
            errorCode: 'UNSUPPORTED_TYPE',
            statusCode: 415,
            context: ['detected_mime' => $detectedMime],
        );
    }

    public static function limitExceeded(string $type, int $limit): self
    {
        return new self(
            message: "Jumlah dokumen {$type} telah mencapai batas maksimal ({$limit}).",
            errorCode: DomainException::CODE_BUSINESS_RULE,
            statusCode: 422,
            context: ['type' => $type, 'limit' => $limit],
        );
    }

    public static function duplicateDocument(string $type): self
    {
        return new self(
            message: "Dokumen {$type} sudah diunggah. Hapus dokumen lama terlebih dahulu jika ingin mengganti.",
            errorCode: DomainException::CODE_CONFLICT,
            statusCode: 409,
            context: ['type' => $type],
        );
    }

    public static function unauthorized(): self
    {
        return new self(
            message: 'Anda tidak memiliki hak untuk melakukan aksi ini pada dokumen.',
            errorCode: DomainException::CODE_NOT_ALLOWED,
            statusCode: 403,
        );
    }

    public static function invalidPortfolioUrl(): self
    {
        return new self(
            message: __('inspector.validation.safe_portfolio_url'),
            errorCode: DomainException::CODE_VALIDATION_ERROR,
            statusCode: 422,
        );
    }
}
