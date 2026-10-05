<?php

declare(strict_types=1);

namespace App\Domains\Listing\Tests\Unit;

use App\Domains\Inspection\Events\JobCompleted;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Listeners\MarkListingInspectedOnJobCompleted;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('LST-T14 marks listing inspected and returns it to active when receiving JobCompleted event', function () {
    $listing = Listing::factory()->inInspection()->create([
        'is_inspected' => false,
        'last_inspected_at' => null,
    ]);

    $event = new JobCompleted(
        jobId: 'job-123',
        listingId: $listing->id,
        inspectorId: 'inspector-123',
        requesterId: 'requester-123',
    );

    /** @var MarkListingInspectedOnJobCompleted $listener */
    $listener = app(MarkListingInspectedOnJobCompleted::class);
    $listener->handle($event);

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Active)
        ->and($fresh->is_inspected)->toBeTrue()
        ->and($fresh->last_inspected_at)->not->toBeNull();
});

it('gracefully handles missing listing when receiving JobCompleted event', function () {
    $event = new JobCompleted(
        jobId: 'job-123',
        listingId: 'non-existent-listing-id',
        inspectorId: 'inspector-123',
        requesterId: 'requester-123',
    );

    /** @var MarkListingInspectedOnJobCompleted $listener */
    $listener = app(MarkListingInspectedOnJobCompleted::class);
    $listener->handle($event);

    expect(true)->toBeTrue();
});
