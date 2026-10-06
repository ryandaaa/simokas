<?php

declare(strict_types=1);

namespace App\Domains\Result\Http\Controllers;

use App\Domains\Identity\Models\User;
use App\Domains\Result\Http\Resources\InspectionResultResource;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Policies\ResultAccessPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class ViewResultController extends Controller
{
    use AuthorizesRequests;

    /**
     * View inspection result with access control.
     * Returns full details if user has access, limited summary otherwise.
     */
    public function __invoke(InspectionResult $result): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $policy = app(ResultAccessPolicy::class);
        $hasFullAccess = $policy->viewDetails($user, $result);

        $resource = (new InspectionResultResource($result))
            ->withDetails($hasFullAccess);

        // Include access fee if user doesn't have access and listing is not sold
        if (! $hasFullAccess && $policy->canRequestPaidAccess($user, $result)) {
            $accessFee = $result->job->fee_amount;
            $resource->withAccessFee($accessFee);
        }

        return $resource->response();
    }
}
