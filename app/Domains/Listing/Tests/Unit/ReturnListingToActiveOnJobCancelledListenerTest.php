<?php

declare(strict_types=1);

namespace App\Domains\Listing\Tests\Unit;

use App\Domains\Inspection\Events\JobCancelled;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Listeners\ReturnListingToActiveOnJobCancelled;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('transitions listing from in_inspection back to active when receiving JobCancelled event', function () {
    $listing = Listing::factory()->inInspection()->create();

    $event = new JobCancelled(
        jobId: 'job-123',
        listingId: $listing->id,
        cancelledBy: 'user-456',
        cancelReason: 'Pembatalan pengujian',
    );

    /** @var ReturnListingToActiveOnJobCancelled $listener */
    $listener = app(ReturnListingToActiveOnJobCancelled::class);
    $listener->handle($event);

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('keeps listing active if it was already active when receiving JobCancelled event', function () {
    $listing = Listing::factory()->active()->create();

    $event = new JobCancelled(
        jobId: 'job-123',
        listingId: $listing->id,
        cancelledBy: 'user-456',
    );

    /** @var ReturnListingToActiveOnJobCancelled $listener */
    $listener = app(ReturnListingToActiveOnJobCancelled::class);
    $listener->handle($event);

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('gracefully handles missing listing when receiving JobCancelled event', function () {
    $event = new JobCancelled(
        jobId: 'job-123',
        listingId: 'non-existent-listing-id',
    );

    /** @var ReturnListingToActiveOnJobCancelled $listener */
    $listener = app(ReturnListingToActiveOnJobCancelled::class);
    $listener->handle($event);

    expect(true)->toBeTrue();
});
