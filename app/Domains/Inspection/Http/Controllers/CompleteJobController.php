<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Http\Controllers;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\CompleteJob;
use App\Domains\Inspection\Models\InspectionJob;
use App\Support\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

final class CompleteJobController extends Controller
{
    public function __invoke(Request $request, string $job, CompleteJob $completeJob): JsonResponse
    {
        /** @var User $requester */
        $requester = $request->user();

        /** @var InspectionJob $inspectionJob */
        $inspectionJob = InspectionJob::query()->findOrFail($job);

        Gate::authorize('complete', $inspectionJob);

        try {
            $updatedJob = $completeJob->handle($inspectionJob);

            return response()->json([
                'data' => [
                    'job_id' => (string) $updatedJob->getKey(),
                    'status' => $updatedJob->status->value,
                    'completed_at' => $updatedJob->completed_at?->toIso8601String(),
                ],
                'error' => null,
            ], 200);
        } catch (DomainException $e) {
            return response()->json([
                'data' => null,
                'error' => [
                    'code' => $e->getErrorCode(),
                    'message' => $e->getMessage(),
                ],
            ], $e->getStatusCode());
        }
    }
}
