<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\ResubmitListing;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingSubmitted;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingModerationException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('LST-T05 resubmits a rejected listing back to pending review', function () {
    $seller = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->rejected('Foto kurang jelas.')->create([
        'seller_id' => $seller->id,
        'validated_by' => $admin->id,
        'validated_at' => now(),
    ]);

    $result = app(ResubmitListing::class)->handle($seller, $listing);

    $fresh = $listing->fresh();
    expect($result->status)->toBe(ListingStatus::PendingReview)
        ->and($fresh->status)->toBe(ListingStatus::PendingReview)
        ->and($fresh->rejection_reason)->toBeNull()
        ->and($fresh->validated_by)->toBeNull()
        ->and($fresh->validated_at)->toBeNull();
});

it('LST-E7 allows resubmission without any changes', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->rejected('Foto kurang jelas.')->create([
        'seller_id' => $seller->id,
    ]);

    $snapshot = $listing->fresh()->only([
        'title', 'brand', 'model', 'year', 'price', 'odometer_km', 'color', 'description',
    ]);

    app(ResubmitListing::class)->handle($seller, $listing);

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::PendingReview)
        ->and($fresh->only(array_keys($snapshot)))->toBe($snapshot);
});

it('LST-T05 dispatches ListingSubmitted exactly once on resubmit', function () {
    Event::fake([ListingSubmitted::class]);

    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->rejected('Foto kurang jelas.')->create([
        'seller_id' => $seller->id,
    ]);

    app(ResubmitListing::class)->handle($seller, $listing);

    Event::assertDispatchedTimes(ListingSubmitted::class, 1);
    Event::assertDispatched(ListingSubmitted::class, fn (ListingSubmitted $event) => $event->listingId === $listing->id
        && $event->sellerId === $seller->id);
});

it('rejects resubmission for listings that are not rejected', function (ListingStatus $status) {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->create([
        'seller_id' => $seller->id,
        'status' => $status,
    ]);

    expect(fn () => app(ResubmitListing::class)->handle($seller, $listing))
        ->toThrow(InvalidListingStatusTransitionException::class);

    expect($listing->fresh()->status)->toBe($status);
})->with([
    [ListingStatus::PendingReview],
    [ListingStatus::Active],
    [ListingStatus::InInspection],
    [ListingStatus::InTransaction],
    [ListingStatus::Sold],
    [ListingStatus::Inactive],
]);

it('forbids resubmission by anyone other than the owner', function (UserRole $role) {
    $seller = User::factory()->user()->create();
    $other = User::factory()->create(['role' => $role]);
    $listing = Listing::factory()->rejected('Foto kurang jelas.')->create([
        'seller_id' => $seller->id,
    ]);

    expect(fn () => app(ResubmitListing::class)->handle($other, $listing))
        ->toThrow(ListingModerationException::class);

    expect($listing->fresh()->status)->toBe(ListingStatus::Rejected);
})->with([
    UserRole::User,
    UserRole::Inspector,
    UserRole::Admin,
]);
