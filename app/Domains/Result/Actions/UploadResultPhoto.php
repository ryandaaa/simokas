<?php

declare(strict_types=1);

namespace App\Domains\Result\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\DTOs\UploadPhotoData;
use App\Domains\Result\Enums\ResultStatus;
use App\Domains\Result\Exceptions\JobNotInProgressException;
use App\Domains\Result\Exceptions\PhotoUploadException;
use App\Domains\Result\Exceptions\ResultAlreadySubmittedException;
use App\Domains\Result\Exceptions\UnauthorizedInspectorException;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultItem;
use App\Domains\Result\Models\ResultPhoto;
use App\Domains\Result\Models\TemplateItem;
use App\Support\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class UploadResultPhoto
{
    private const int MAX_BYTES = 2 * 1024 * 1024; // 2 MB

    private const array ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(
        private readonly GetOrCreateDraftResult $getOrCreateDraft,
    ) {}

    public function handle(
        User $inspector,
        InspectionJob|InspectionResult $target,
        UploadPhotoData $data,
    ): ResultPhoto {
        if ($target instanceof InspectionJob) {
            $job = $target;
            $result = $this->getOrCreateDraft->handle($inspector, $job);
        } else {
            $result = $target;
            $job = $result->job;
        }

        // 1. Authorization & Job/Result state check
        if ((string) $inspector->getKey() !== (string) $job->inspector_id) {
            throw UnauthorizedInspectorException::create();
        }

        if ($result->status !== ResultStatus::Draft) {
            throw ResultAlreadySubmittedException::create();
        }

        if ($job->status !== JobStatus::InProgress) {
            throw JobNotInProgressException::create();
        }

        // 2. Camera capture check (RES-R5, RES-E5)
        if (! $data->isCameraCapture) {
            throw PhotoUploadException::notCameraCapture();
        }

        // 3. File size check (RES-E1)
        if ($data->file->getSize() > self::MAX_BYTES) {
            throw PhotoUploadException::fileTooLarge(self::MAX_BYTES);
        }

        // 4. Real MIME check (RES-E1)
        $realMime = (string) $data->file->getMimeType();
        if (! in_array($realMime, self::ALLOWED_MIMES, true)) {
            throw PhotoUploadException::unsupportedType($realMime);
        }

        // 5. Template item verification
        /** @var TemplateItem|null $templateItem */
        $templateItem = TemplateItem::query()
            ->where('template_id', $result->template_id)
            ->whereKey($data->templateItemId)
            ->first();

        if ($templateItem === null) {
            throw new DomainException(
                'Item template inspeksi tidak ditemukan.',
                'TEMPLATE_ITEM_NOT_FOUND',
                422,
            );
        }

        // 6. Strip EXIF/GPS & Store in private disk
        $extension = match ($realMime) {
            'image/webp' => 'webp',
            'image/png' => 'png',
            default => 'jpg',
        };

        $randomFileName = Str::uuid()->toString().'.'.$extension;
        $directory = "results/{$result->id}/photos";
        $storedPath = "{$directory}/{$randomFileName}";

        $cleanContent = $this->stripExif($data->file->getPathname(), $realMime);

        return DB::transaction(function () use ($result, $templateItem, $data, $storedPath, $directory, $cleanContent): ResultPhoto {
            // Find or create the ResultItem
            /** @var ResultItem $resultItem */
            $resultItem = ResultItem::query()->firstOrCreate(
                [
                    'result_id' => $result->getKey(),
                    'template_item_id' => $templateItem->getKey(),
                ]
            );

            // Clean up any existing photo on this result item (retake behavior)
            $existingPhotos = ResultPhoto::query()
                ->where('result_item_id', $resultItem->getKey())
                ->get();

            foreach ($existingPhotos as $existingPhoto) {
                Storage::disk('private')->delete($existingPhoto->storage_path);
                $existingPhoto->delete();
            }

            // Write new photo to private disk
            if ($cleanContent !== null) {
                Storage::disk('private')->put($storedPath, $cleanContent);
            } else {
                Storage::disk('private')->putFileAs($directory, $data->file, basename($storedPath));
            }

            // Create ResultPhoto record: captured_at is server time (RES-R6)
            /** @var ResultPhoto $photo */
            $photo = ResultPhoto::query()->create([
                'result_id' => $result->getKey(),
                'result_item_id' => $resultItem->getKey(),
                'storage_path' => $storedPath,
                'captured_at' => now(),
                'stamped_at' => $data->stampedAt ?? now(),
                'is_camera_capture' => true,
            ]);

            return $photo;
        });
    }

    /**
     * Strips EXIF and GPS metadata by re-encoding via GD if available.
     */
    private function stripExif(string $filePath, string $mime): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $raw = @file_get_contents($filePath);
        if ($raw === false) {
            return null;
        }

        $image = @imagecreatefromstring($raw);
        if ($image === false) {
            return null;
        }

        ob_start();
        if ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagewebp($image, null, 90);
        } elseif ($mime === 'image/png' && function_exists('imagepng')) {
            imagepng($image, null, 9);
        } else {
            imagejpeg($image, null, 90);
        }
        $encoded = ob_get_clean();
        imagedestroy($image);

        return ($encoded !== false && strlen($encoded) > 0) ? $encoded : null;
    }
}
