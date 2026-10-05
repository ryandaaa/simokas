<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Events\JobPaid;
use App\Domains\Inspection\Listeners\MarkJobPaidOnPaymentSucceeded;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Payment\Enums\PaymentPurpose;
use App\Domains\Payment\Events\PaymentSucceeded;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('ignores PaymentSucceeded events for purposes other than Inspection', function () {
    Event::fake([JobPaid::class]);

    $job = InspectionJob::factory()->requested()->create();

    $event = new PaymentSucceeded(
        paymentId: 'pay-123',
        purpose: PaymentPurpose::Purchase,
        refId: $job->id,
        amount: 50000,
        payerId: $job->requester_id,
    );

    $listener = new MarkJobPaidOnPaymentSucceeded;
    $listener->handle($event);

    expect($job->fresh()->status)->toBe(JobStatus::Requested);
    Event::assertNotDispatched(JobPaid::class);
});

it('marks requested job as paid, records available_since, and dispatches JobPaid', function () {
    Event::fake([JobPaid::class]);

    $job = InspectionJob::factory()->requested()->create();

    $event = new PaymentSucceeded(
        paymentId: 'pay-123',
        purpose: PaymentPurpose::Inspection,
        refId: $job->id,
        amount: 50000,
        payerId: $job->requester_id,
    );

    $listener = new MarkJobPaidOnPaymentSucceeded;
    $listener->handle($event);

    $freshJob = $job->fresh();
    expect($freshJob->status)->toBe(JobStatus::Paid)
        ->and($freshJob->available_since)->not->toBeNull();

    Event::assertDispatched(JobPaid::class, function (JobPaid $dispatched) use ($job) {
        return $dispatched->jobId === $job->id
            && $dispatched->listingId === $job->listing_id
            && $dispatched->requesterId === $job->requester_id;
    });
});

it('is idempotent: duplicate PaymentSucceeded event does not dispatch second JobPaid event', function () {
    Event::fake([JobPaid::class]);

    $job = InspectionJob::factory()->requested()->create();

    $event = new PaymentSucceeded(
        paymentId: 'pay-123',
        purpose: PaymentPurpose::Inspection,
        refId: $job->id,
        amount: 50000,
        payerId: $job->requester_id,
    );

    $listener = new MarkJobPaidOnPaymentSucceeded;
    $listener->handle($event);
    $firstAvailableSince = $job->fresh()->available_since;

    // Second call with same event
    $listener->handle($event);

    expect($job->fresh()->status)->toBe(JobStatus::Paid)
        ->and($job->fresh()->available_since->toDateTimeString())->toBe($firstAvailableSince->toDateTimeString());

    // Only one JobPaid event was dispatched
    Event::assertDispatchedTimes(JobPaid::class, 1);
});
