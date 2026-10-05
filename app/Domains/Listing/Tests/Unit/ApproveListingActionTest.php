<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\ApproveListing;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingApproved;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingModerationException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('LST-T04 approves a pending listing to active and records the validator', function () {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create();

    $result = app(ApproveListing::class)->handle($admin, $listing);

    $fresh = $listing->fresh();
    expect($result->status)->toBe(ListingStatus::Active)
        ->and($fresh->status)->toBe(ListingStatus::Active)
        ->and($fresh->validated_by)->toBe($admin->id)
        ->and($fresh->validated_at)->not->toBeNull()
        ->and($fresh->rejection_reason)->toBeNull();
});

it('LST-T04 dispatches ListingApproved exactly once with ids only', function () {
    Event::fake([ListingApproved::class]);

    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create();

    app(ApproveListing::class)->handle($admin, $listing);

    Event::assertDispatchedTimes(ListingApproved::class, 1);
    Event::assertDispatched(ListingApproved::class, fn (ListingApproved $event) => $event->listingId === $listing->id
        && $event->sellerId === $listing->seller_id
        && $event->approvedBy === $admin->id);
});

it('rejects approval for listings that are not pending review', function (ListingStatus $status) {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->create(['status' => $status]);

    expect(fn () => app(ApproveListing::class)->handle($admin, $listing))
        ->toThrow(InvalidListingStatusTransitionException::class);

    expect($listing->fresh()->status)->toBe($status);
})->with([
    [ListingStatus::Active],
    [ListingStatus::Rejected],
    [ListingStatus::InInspection],
    [ListingStatus::InTransaction],
    [ListingStatus::Sold],
    [ListingStatus::Inactive],
]);

it('forbids non-admin users from approving listings', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $listing = Listing::factory()->pendingReview()->create();

    expect(fn () => app(ApproveListing::class)->handle($user, $listing))
        ->toThrow(ListingModerationException::class);

    expect($listing->fresh()->status)->toBe(ListingStatus::PendingReview);
})->with([
    UserRole::User,
    UserRole::Inspector,
]);

it('LST-T16 second approval on the same listing fails without double effect', function () {
    $firstAdmin = User::factory()->admin()->create();
    $secondAdmin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create();

    app(ApproveListing::class)->handle($firstAdmin, $listing);

    expect(fn () => app(ApproveListing::class)->handle($secondAdmin, $listing->fresh()))
        ->toThrow(InvalidListingStatusTransitionException::class);

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Active)
        ->and($fresh->validated_by)->toBe($firstAdmin->id);
});

it('forbids an admin from approving their own listing', function () {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create(['seller_id' => $admin->id]);

    expect(fn () => app(ApproveListing::class)->handle($admin, $listing))
        ->toThrow(ListingModerationException::class);

    expect($listing->fresh()->status)->toBe(ListingStatus::PendingReview);
});
