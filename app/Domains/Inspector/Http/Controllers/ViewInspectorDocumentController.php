<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Http\Controllers;

use App\Domains\Inspector\Models\InspectorDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class ViewInspectorDocumentController
{
    public function __invoke(Request $request, InspectorDocument $document): Response
    {
        Gate::authorize('view', $document);

        if ($document->storage_path === null || ! Storage::disk('private')->exists($document->storage_path)) {
            abort(404, __('inspector.documents.not_found'));
        }

        $originalName = (string) ($document->metadata['original_filename'] ?? basename($document->storage_path));

        return Storage::disk('private')->response(
            $document->storage_path,
            $originalName,
            [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ],
            'inline'
        );
    }
}
