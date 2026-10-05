<?php

declare(strict_types=1);

namespace App\Domains\Result\Exceptions;

use App\Support\Exceptions\DomainException;

final class PhotoUploadException extends DomainException
{
    public static function notCameraCapture(): self
    {
        return new self(
            __('result.photos.not_camera_capture'),
            'NOT_CAMERA_CAPTURE',
            422,
        );
    }

    public static function fileTooLarge(int $maxBytes = 2097152): self
    {
        return new self(
            __('result.photos.file_too_large'),
            'FILE_TOO_LARGE',
            413,
            null,
            ['max_bytes' => $maxBytes],
        );
    }

    public static function unsupportedType(string $mime): self
    {
        return new self(
            __('result.photos.unsupported_type'),
            'UNSUPPORTED_TYPE',
            415,
            null,
            ['mime' => $mime],
        );
    }

    public static function notFound(): self
    {
        return new self(
            __('result.photos.not_found'),
            'PHOTO_NOT_FOUND',
            404,
        );
    }
}
