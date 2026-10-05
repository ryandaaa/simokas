<?php

declare(strict_types=1);

namespace App\Domains\Result\Http\Controllers;

use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Result\Actions\DeleteResultPhoto;
use App\Domains\Result\Models\ResultPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class DeleteResultPhotoController
{
    public function __invoke(
        Request $request,
        InspectionJob $job,
        ResultPhoto $photo,
        DeleteResultPhoto $deletePhoto,
    ): JsonResponse {
        $deletePhoto->handle($request->user(), $job, $photo);

        return response()->json([
            'data' => [
                'deleted' => true,
            ],
            'error' => null,
        ], Response::HTTP_OK);
    }
}
