<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Http\Controllers;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\TakeJob;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Models\Listing;
use App\Support\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

final class TakeJobController extends Controller
{
    public function __invoke(Request $request, string $job, TakeJob $takeJob): JsonResponse
    {
        /** @var User $inspector */
        $inspector = $request->user();

        /** @var InspectionJob $inspectionJob */
        $inspectionJob = InspectionJob::query()->findOrFail($job);

        Gate::authorize('take', $inspectionJob);

        try {
            $updatedJob = $takeJob->handle($inspector, $inspectionJob);

            /** @var Listing|null $listing */
            $listing = Listing::query()
                ->select(['lat', 'lng', 'address_full', 'chassis_number'])
                ->whereKey($updatedJob->listing_id)
                ->first();

            return response()->json([
                'data' => [
                    'job_id' => (string) $updatedJob->getKey(),
                    'status' => $updatedJob->status->value,
                    'location' => [
                        'lat' => $listing?->lat !== null ? (float) $listing->lat : null,
                        'lng' => $listing?->lng !== null ? (float) $listing->lng : null,
                        'address_full' => $listing?->address_full,
                    ],
                    'chassis_number' => $listing?->chassis_number,
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
