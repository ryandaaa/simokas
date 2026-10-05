<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Listing\Actions\DeactivateListing;
use App\Domains\Listing\Actions\UpdateListing;
use App\Domains\Listing\DTOs\UpdateListingDTO;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Exceptions\ListingLockedException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('locks listing deactivation when listing has an active inspection job', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'status' => JobStatus::Requested,
    ]);

    /** @var DeactivateListing $deactivateAction */
    $deactivateAction = app(DeactivateListing::class);

    // Attempt to deactivate should be rejected because of active job
    expect(fn () => $deactivateAction->handle($seller, $listing))
        ->toThrow(ListingLockedException::class);

    // Once the job is completed, deactivation succeeds
    $job->update(['status' => JobStatus::Completed]);

    $result = $deactivateAction->handle($seller, $listing);
    expect($result->status)->toBe(ListingStatus::Inactive);
});

it('locks listing core fields edit when listing has an active inspection job', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'price' => 100000000,
    ]);

    $job = InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'status' => JobStatus::Paid,
    ]);

    /** @var UpdateListing $updateAction */
    $updateAction = app(UpdateListing::class);

    // Changing price (a core field) is rejected while active job exists
    $dto = new UpdateListingDTO(
        actor: $seller,
        listing: $listing,
        fields: ['price' => 120000000],
    );

    expect(fn () => $updateAction->handle($dto))
        ->toThrow(ListingLockedException::class);

    // Once the job is cancelled, editing price succeeds
    $job->update(['status' => JobStatus::Cancelled]);

    $updatedListing = $updateAction->handle($dto);
    expect($updatedListing->price)->toBe(120000000);
});
