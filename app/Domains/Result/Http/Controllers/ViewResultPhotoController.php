<?php

declare(strict_types=1);

namespace App\Domains\Result\Http\Controllers;

use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Models\ResultPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class ViewResultPhotoController
{
    public function __invoke(Request $request, InspectionResult $result, ResultPhoto $photo): Response
    {
        if ($photo->result_id !== $result->id) {
            abort(404, __('result.photos.not_found'));
        }

        Gate::authorize('viewPhoto', [$result, $photo]);

        if (! Storage::disk('private')->exists($photo->storage_path)) {
            abort(404, __('result.photos.not_found'));
        }

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ];

        $filename = basename($photo->storage_path);

        return Storage::disk('private')->response(
            $photo->storage_path,
            $filename,
            $headers,
            'inline'
        );
    }
}
