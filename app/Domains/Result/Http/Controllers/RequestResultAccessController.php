<?php

declare(strict_types=1);

namespace App\Domains\Result\Http\Controllers;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Policies\ResultAccessPolicy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class RequestResultAccessController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(InspectionResult $result, Billing $billing): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Check if user can request paid access
        $policy = app(ResultAccessPolicy::class);
        if (! $policy->canRequestPaidAccess($user, $result)) {
            return back()->with('error', __('result.access_not_available'));
        }

        // Create bill for access using inspection fee amount
        $accessFee = $result->job->fee_amount;

        $billData = new CreateBillData(
            payerId: $user->getKey(),
            purpose: PaymentPurpose::Access,
            refId: $result->getKey(),
            amount: $accessFee,
            idempotencyKey: (string) Str::uuid(),
        );

        $bill = $billing->createBill($billData);

        // Redirect to payment page
        return redirect()->route('payments.show', $bill->id);
    }
}
