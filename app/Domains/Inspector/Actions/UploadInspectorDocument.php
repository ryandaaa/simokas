<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Enums\DocumentType;
use App\Domains\Inspector\Exceptions\DocumentUploadException;
use App\Domains\Inspector\Exceptions\InvalidInspectorProfileException;
use App\Domains\Inspector\Models\InspectorDocument;
use App\Domains\Inspector\Rules\SafePortfolioUrl;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class UploadInspectorDocument
{
    public function handle(User $user, DocumentType $type, UploadedFile|string $fileOrUrl): InspectorDocument
    {
        if (! $user->isInspector() && ! $user->isAdmin()) {
            throw InvalidInspectorProfileException::notAnInspector();
        }

        return DB::transaction(function () use ($user, $type, $fileOrUrl): InspectorDocument {
            $existingCount = InspectorDocument::query()
                ->where('user_id', $user->id)
                ->where('type', $type->value)
                ->lockForUpdate()
                ->count();

            if ($type === DocumentType::Certificate) {
                if ($existingCount >= $type->maxCount()) {
                    throw DocumentUploadException::limitExceeded($type->value, $type->maxCount());
                }
            } else {
                if ($existingCount >= $type->maxCount()) {
                    throw DocumentUploadException::duplicateDocument($type->value);
                }
            }

            if (is_string($fileOrUrl)) {
                if (! $type->allowsUrl()) {
                    throw DocumentUploadException::unsupportedType('url');
                }

                if (! SafePortfolioUrl::isValid($fileOrUrl)) {
                    throw DocumentUploadException::invalidPortfolioUrl();
                }

                /** @var InspectorDocument $document */
                $document = InspectorDocument::query()->create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'storage_path' => null,
                    'metadata' => [
                        'url' => $fileOrUrl,
                    ],
                ]);

                return $document;
            }

            // Validasi ukuran berkas (maks 2 MB)
            if ($fileOrUrl->getSize() > $type->maxSizeBytes()) {
                throw DocumentUploadException::fileTooLarge($type->maxSizeBytes());
            }

            // Periksa MIME sebenarnya dari isi berkas menggunakan finfo
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $realMime = $finfo->file($fileOrUrl->getRealPath());

            if ($realMime === false || ! in_array($realMime, $type->allowedMimeTypes(), true)) {
                throw DocumentUploadException::unsupportedType((string) $realMime);
            }

            // Simpan dengan nama berkas acak (tanpa nama asli) di disk privat
            $extension = $fileOrUrl->guessExtension() ?: $fileOrUrl->getClientOriginalExtension();
            $randomFileName = Str::uuid()->toString().'.'.strtolower($extension);
            $directory = "inspector-documents/{$user->id}";

            $storedPath = Storage::disk('private')->putFileAs($directory, $fileOrUrl, $randomFileName);
            if (! is_string($storedPath)) {
                $storedPath = "{$directory}/{$randomFileName}";
            }

            /** @var InspectorDocument $document */
            $document = InspectorDocument::query()->create([
                'user_id' => $user->id,
                'type' => $type,
                'storage_path' => $storedPath,
                'metadata' => [
                    'original_filename' => $fileOrUrl->getClientOriginalName(),
                    'mime_type' => $realMime,
                    'size' => $fileOrUrl->getSize(),
                ],
            ]);

            return $document;
        });
    }
}
