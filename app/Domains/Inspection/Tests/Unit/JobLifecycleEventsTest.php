<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Inspection\Events\JobExpired;
use App\Domains\Inspection\Events\JobPaid;
use App\Domains\Inspection\Events\JobScheduled;
use App\Domains\Inspection\Events\JobStarted;
use App\Domains\Inspection\Events\JobTaken;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('JOB-T19 instantiates and verifies payload structure of JobExpired event', function () {
    $event = new JobExpired(
        jobId: 'job-uuid-1',
        listingId: 'listing-uuid-1',
        requesterId: 'user-uuid-1',
    );

    expect($event->jobId)->toBe('job-uuid-1')
        ->and($event->listingId)->toBe('listing-uuid-1')
        ->and($event->requesterId)->toBe('user-uuid-1');
});

it('JOB-T19 instantiates and verifies payload structure of JobCompleted event', function () {
    $event = new JobCompleted(
        jobId: 'job-uuid-2',
        listingId: 'listing-uuid-2',
        inspectorId: 'inspector-uuid-2',
        requesterId: 'requester-uuid-2',
    );

    expect($event->jobId)->toBe('job-uuid-2')
        ->and($event->listingId)->toBe('listing-uuid-2')
        ->and($event->inspectorId)->toBe('inspector-uuid-2')
        ->and($event->requesterId)->toBe('requester-uuid-2');
});

it('JOB-T19 instantiates and verifies payload structure of JobCancelled event', function () {
    $event = new JobCancelled(
        jobId: 'job-uuid-3',
        listingId: 'listing-uuid-3',
        cancelledBy: null,
        cancelReason: 'Dibatalkan otomatis',
    );

    expect($event->jobId)->toBe('job-uuid-3')
        ->and($event->listingId)->toBe('listing-uuid-3')
        ->and($event->cancelledBy)->toBeNull()
        ->and($event->cancelReason)->toBe('Dibatalkan otomatis');
});
