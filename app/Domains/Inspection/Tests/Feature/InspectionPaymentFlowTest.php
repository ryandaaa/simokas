<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Actions\RequestInspection;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Models\Listing;
use App\Domains\Payment\Actions\ProcessPaymentResult;
use App\Domains\Payment\DTOs\ProcessPaymentData;
use App\Domains\Payment\Enums\PaymentMethod;
use App\Domains\Payment\Enums\PaymentStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('executes full end-to-end flow: request inspection -> successful payment -> job paid -> listing in_inspection', function () {
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create();

    // 1. Requester requests inspection
    /** @var RequestInspection $requestAction */
    $requestAction = app(RequestInspection::class);
    $result = $requestAction->handle($requester, $listing);

    $job = $result['job'];
    $bill = $result['bill'];

    expect($job->status)->toBe(JobStatus::Requested)
        ->and($listing->fresh()->status)->toBe(ListingStatus::Active);

    // 2. Process payment with success result via real Payment action
    /** @var ProcessPaymentResult $paymentAction */
    $paymentAction = app(ProcessPaymentResult::class);
    $paymentResult = $paymentAction->handle(new ProcessPaymentData(
        paymentId: $bill->id,
        targetStatus: PaymentStatus::Success,
        method: PaymentMethod::Qris,
    ));

    expect($paymentResult->payment->status)->toBe(PaymentStatus::Success);

    // 3. Verify real event reaction in Inspection: job is paid and available_since is recorded
    $freshJob = $job->fresh();
    expect($freshJob->status)->toBe(JobStatus::Paid)
        ->and($freshJob->available_since)->not->toBeNull();

    // 4. Verify real cross-domain event reaction in Listing: listing is now in_inspection
    expect($listing->fresh()->status)->toBe(ListingStatus::InInspection);
});

it('does not transition job or listing when payment is failed or pending (PAY-R10)', function (PaymentStatus $nonSuccessStatus) {
    $requester = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create();

    /** @var RequestInspection $requestAction */
    $requestAction = app(RequestInspection::class);
    $result = $requestAction->handle($requester, $listing);

    $job = $result['job'];
    $bill = $result['bill'];

    // Process payment with non-success status
    /** @var ProcessPaymentResult $paymentAction */
    $paymentAction = app(ProcessPaymentResult::class);
    $paymentAction->handle(new ProcessPaymentData(
        paymentId: $bill->id,
        targetStatus: $nonSuccessStatus,
        method: PaymentMethod::BankTransfer,
    ));

    // Job stays requested, listing stays active
    expect($job->fresh()->status)->toBe(JobStatus::Requested)
        ->and($job->fresh()->available_since)->toBeNull()
        ->and($listing->fresh()->status)->toBe(ListingStatus::Active);
})->with([
    [PaymentStatus::Failed],
    [PaymentStatus::Pending],
]);
