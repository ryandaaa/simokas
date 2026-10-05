<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Exceptions\DocumentUploadException;
use App\Domains\Inspector\Models\InspectorDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class RemoveInspectorDocument
{
    public function handle(User $actor, InspectorDocument $document): void
    {
        if ($actor->id !== $document->user_id && ! $actor->isAdmin()) {
            throw DocumentUploadException::unauthorized();
        }

        DB::transaction(function () use ($document): void {
            if ($document->storage_path !== null && Storage::disk('private')->exists($document->storage_path)) {
                Storage::disk('private')->delete($document->storage_path);
            }

            $document->delete();
        });
    }
}
