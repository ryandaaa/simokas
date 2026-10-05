<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Enums;

enum DocumentType: string
{
    case Ktp = 'ktp';
    case Certificate = 'certificate';
    case Cv = 'cv';
    case Portfolio = 'portfolio';

    public function label(): string
    {
        return match ($this) {
            self::Ktp => __('inspector.documents.types.ktp'),
            self::Certificate => __('inspector.documents.types.certificate'),
            self::Cv => __('inspector.documents.types.cv'),
            self::Portfolio => __('inspector.documents.types.portfolio'),
        };
    }

    public function maxCount(): int
    {
        return match ($this) {
            self::Certificate => 3,
            default => 1,
        };
    }

    public function maxSizeBytes(): int
    {
        return 2 * 1024 * 1024; // 2 MB
    }

    /**
     * @return array<int, string>
     */
    public function allowedMimeTypes(): array
    {
        return match ($this) {
            self::Ktp => [
                'image/jpeg',
                'image/png',
                'application/pdf',
            ],
            self::Certificate => [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ],
            self::Cv => [
                'application/pdf',
            ],
            self::Portfolio => [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::Ktp => ['jpg', 'jpeg', 'png', 'pdf'],
            self::Certificate => ['pdf', 'jpg', 'jpeg', 'png'],
            self::Cv => ['pdf'],
            self::Portfolio => ['pdf', 'jpg', 'jpeg', 'png'],
        };
    }

    public function allowsUrl(): bool
    {
        return $this === self::Portfolio;
    }
}
