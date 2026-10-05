<?php

declare(strict_types=1);

namespace App\Domains\Result\Http\Controllers;

use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Actions\UploadResultPhoto;
use App\Domains\Result\DTOs\UploadPhotoData;
use App\Domains\Result\Http\Requests\UploadResultPhotoRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class UploadResultPhotoController
{
    public function __invoke(
        UploadResultPhotoRequest $request,
        InspectionJob $job,
        UploadResultPhoto $uploadPhoto,
    ): JsonResponse {
        $stampedAt = $request->filled('stamped_at')
            ? Carbon::parse((string) $request->input('stamped_at'))
            : now();

        $dto = new UploadPhotoData(
            file: $request->file('file'),
            templateItemId: (string) $request->input('template_item_id'),
            stampedAt: $stampedAt,
            isCameraCapture: $request->boolean('is_camera_capture'),
        );

        $photo = $uploadPhoto->handle($request->user(), $job, $dto);

        return response()->json([
            'data' => [
                'photo_id' => $photo->id,
                'captured_at' => $photo->captured_at->toIso8601String(),
            ],
            'error' => null,
        ], Response::HTTP_CREATED);
    }
}
