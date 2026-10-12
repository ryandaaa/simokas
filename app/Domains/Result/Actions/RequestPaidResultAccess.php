<?php

declare(strict_types=1);

namespace App\Domains\Result\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Payment\Contracts\Billing;
use App\Domains\Payment\DTOs\CreateBillData;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Result\Models\InspectionResult;
use App\Domains\Result\Policies\ResultAccessPolicy;
use Illuminate\Support\Str;

/**
 * Create the bill for paid access to an inspection result (RES-R, access fee = inspection fee).
 *
 * Shared by the POST route and the result page, so both paths behave the same.
 * Returns the payment id to redirect to, or null when access cannot be requested
 * (already has access, listing sold, etc.).
 */
final class RequestPaidResultAccess
{
    public function __construct(
        private readonly Billing $billing,
        private readonly ResultAccessPolicy $policy,
    ) {}

    public function handle(User $user, InspectionResult $result): ?string
    {
        if (! $this->policy->canRequestPaidAccess($user, $result)) {
            return null;
        }

        $bill = $this->billing->createBill(new CreateBillData(
            payerId: $user->getKey(),
            purpose: PaymentPurpose::Access,
            refId: $result->getKey(),
            amount: $result->job->fee_amount,
            idempotencyKey: (string) Str::uuid(),
        ));

        return (string) $bill->id;
    }
}
