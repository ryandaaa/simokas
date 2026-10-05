<?php

declare(strict_types=1);

namespace App\Domains\Listing\Tests\Unit;

use App\Domains\Inspection\Events\JobExpired;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Listeners\ReturnListingToActiveOnJobExpired;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('transitions listing from in_inspection back to active when receiving JobExpired event', function () {
    $listing = Listing::factory()->inInspection()->create();

    $event = new JobExpired(
        jobId: 'job-123',
        listingId: $listing->id,
        requesterId: 'user-456',
    );

    /** @var ReturnListingToActiveOnJobExpired $listener */
    $listener = app(ReturnListingToActiveOnJobExpired::class);
    $listener->handle($event);

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('keeps listing active if it was already active when receiving JobExpired event', function () {
    $listing = Listing::factory()->active()->create();

    $event = new JobExpired(
        jobId: 'job-123',
        listingId: $listing->id,
        requesterId: 'user-456',
    );

    /** @var ReturnListingToActiveOnJobExpired $listener */
    $listener = app(ReturnListingToActiveOnJobExpired::class);
    $listener->handle($event);

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('gracefully handles missing listing when receiving JobExpired event', function () {
    $event = new JobExpired(
        jobId: 'job-123',
        listingId: 'non-existent-listing-id',
    );

    /** @var ReturnListingToActiveOnJobExpired $listener */
    $listener = app(ReturnListingToActiveOnJobExpired::class);
    $listener->handle($event);

    expect(true)->toBeTrue();
});
