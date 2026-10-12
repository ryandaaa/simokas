<?php

declare(strict_types=1);

namespace App\Domains\Result\Http\Controllers;

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Result\Actions\RequestPaidResultAccess;
use App\Domains\Result\Models\InspectionResult;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class RequestResultAccessController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Listing $listing, RequestPaidResultAccess $requestAccess): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Find the inspection result for this listing
        $result = InspectionResult::whereHas('job', function ($query) use ($listing) {
            $query->where('listing_id', $listing->id);
        })->first();

        if ($result === null) {
            abort(404);
        }

        $paymentId = $requestAccess->handle($user, $result);

        if ($paymentId === null) {
            return back()->with('error', __('result.access_not_available'));
        }

        // Redirect to payment page
        return redirect()->route('payments.show', $paymentId);
    }
}
