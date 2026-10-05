<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Inspection\Enums\JobStatus;
use App\Domains\Inspection\Models\InspectionJob;
use App\Domains\Inspection\Services\InspectionListingActivityChecker;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('reports active job correctly across all job statuses', function (JobStatus $status, bool $expected) {
    $listing = Listing::factory()->active()->create();

    InspectionJob::factory()->create([
        'listing_id' => $listing->id,
        'status' => $status,
    ]);

    $checker = app(ListingActivityChecker::class);

    expect($checker->hasActiveJob($listing->id))->toBe($expected);
})->with([
    'requested' => [JobStatus::Requested, true],
    'paid' => [JobStatus::Paid, true],
    'taken' => [JobStatus::Taken, true],
    'scheduled' => [JobStatus::Scheduled, true],
    'in_progress' => [JobStatus::InProgress, true],
    'result_submitted' => [JobStatus::ResultSubmitted, true],
    'completed' => [JobStatus::Completed, false],
    'cancelled' => [JobStatus::Cancelled, false],
    'expired' => [JobStatus::Expired, false],
]);

it('delegates hasActiveTransaction to fallback and returns false', function () {
    $listing = Listing::factory()->active()->create();

    $checker = app(ListingActivityChecker::class);

    expect($checker->hasActiveTransaction($listing->id))->toBeFalse();
});

it('ensures Inspection binding wins over Listing default binding', function () {
    $checker = app(ListingActivityChecker::class);

    expect($checker)->toBeInstanceOf(InspectionListingActivityChecker::class);
});
