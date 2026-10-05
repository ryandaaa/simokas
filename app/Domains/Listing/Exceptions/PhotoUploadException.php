<?php

declare(strict_types=1);

namespace App\Domains\Listing\Exceptions;

use App\Support\Exceptions\DomainException;

final class PhotoUploadException extends DomainException
{
    public static function countInvalid(int $count): self
    {
        return new self(
            __('listing.validation.photos_count'),
            self::CODE_VALIDATION_ERROR,
            422,
            null,
            ['count' => $count]
        );
    }

    public static function fileTooLarge(int $maxBytes): self
    {
        return new self(
            __('listing.validation.photo_too_large'),
            'FILE_TOO_LARGE',
            413,
            null,
            ['max_bytes' => $maxBytes]
        );
    }

    public static function unsupportedType(string $mime): self
    {
        return new self(
            __('listing.validation.photo_invalid_type'),
            'UNSUPPORTED_TYPE',
            415,
            null,
            ['mime' => $mime]
        );
    }

    public static function oneCoverRequired(): self
    {
        return new self(
            __('listing.validation.one_cover_required'),
            self::CODE_VALIDATION_ERROR,
            422
        );
    }
}
