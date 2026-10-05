<?php

declare(strict_types=1);

namespace App\Domains\Result\DTOs;

use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;

final readonly class UploadPhotoData
{
    public function __construct(
        public UploadedFile $file,
        public string $templateItemId,
        public ?CarbonInterface $stampedAt = null,
        public bool $isCameraCapture = false,
    ) {}
}
