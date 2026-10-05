<?php

declare(strict_types=1);

namespace App\Domains\Listing\Tests\Unit;

use App\Domains\Inspection\Events\JobPaid;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Listeners\MarkListingInInspectionOnJobPaid;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('transitions listing to in_inspection when receiving JobPaid event', function () {
    $listing = Listing::factory()->active()->create();

    $event = new JobPaid(
        jobId: 'job-123',
        listingId: $listing->id,
        requesterId: 'user-456',
    );

    /** @var MarkListingInInspectionOnJobPaid $listener */
    $listener = app(MarkListingInInspectionOnJobPaid::class);
    $listener->handle($event);

    expect($listing->fresh()->status)->toBe(ListingStatus::InInspection);
});

it('gracefully handles missing listing when receiving JobPaid event', function () {
    $event = new JobPaid(
        jobId: 'job-123',
        listingId: 'non-existent-listing-id',
        requesterId: 'user-456',
    );

    /** @var MarkListingInInspectionOnJobPaid $listener */
    $listener = app(MarkListingInInspectionOnJobPaid::class);
    $listener->handle($event);

    // No exception thrown
    expect(true)->toBeTrue();
});
