<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\RejectListing;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingRejected;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingModerationException;
use App\Domains\Listing\Exceptions\MissingRejectionReasonException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('LST-T04 rejects a pending listing with reason and records the validator', function () {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create();
    $reason = 'Foto kurang jelas, mohon unggah ulang foto asli.';

    $result = app(RejectListing::class)->handle($admin, $listing, $reason);

    $fresh = $listing->fresh();
    expect($result->status)->toBe(ListingStatus::Rejected)
        ->and($fresh->status)->toBe(ListingStatus::Rejected)
        ->and($fresh->rejection_reason)->toBe($reason)
        ->and($fresh->validated_by)->toBe($admin->id)
        ->and($fresh->validated_at)->not->toBeNull();
});

it('LST-T04 dispatches ListingRejected exactly once with ids and reason', function () {
    Event::fake([ListingRejected::class]);

    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create();
    $reason = 'Data tidak sesuai dengan foto.';

    app(RejectListing::class)->handle($admin, $listing, $reason);

    Event::assertDispatchedTimes(ListingRejected::class, 1);
    Event::assertDispatched(ListingRejected::class, fn (ListingRejected $event) => $event->listingId === $listing->id
        && $event->sellerId === $listing->seller_id
        && $event->rejectedBy === $admin->id
        && $event->reason === $reason);
});

it('requires a rejection reason and rejects blank reasons', function (?string $reason) {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create();

    expect(fn () => app(RejectListing::class)->handle($admin, $listing, (string) $reason))
        ->toThrow(MissingRejectionReasonException::class);

    expect($listing->fresh()->status)->toBe(ListingStatus::PendingReview);
})->with([
    'empty string' => [''],
    'only spaces' => ['   '],
    'only newline and tabs' => ["\n\t "],
]);

it('rejects rejection for listings that are not pending review', function (ListingStatus $status) {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->create(['status' => $status]);

    expect(fn () => app(RejectListing::class)->handle($admin, $listing, 'Alasan yang jelas.'))
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

it('forbids non-admin users from rejecting listings', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);
    $listing = Listing::factory()->pendingReview()->create();

    expect(fn () => app(RejectListing::class)->handle($user, $listing, 'Alasan yang jelas.'))
        ->toThrow(ListingModerationException::class);

    expect($listing->fresh()->status)->toBe(ListingStatus::PendingReview);
})->with([
    UserRole::User,
    UserRole::Inspector,
]);

it('LST-T16 second rejection on the same listing fails without double effect', function () {
    $firstAdmin = User::factory()->admin()->create();
    $secondAdmin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create();

    app(RejectListing::class)->handle($firstAdmin, $listing, 'Alasan pertama yang jelas.');

    expect(fn () => app(RejectListing::class)->handle($secondAdmin, $listing->fresh(), 'Alasan kedua.'))
        ->toThrow(InvalidListingStatusTransitionException::class);

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Rejected)
        ->and($fresh->rejection_reason)->toBe('Alasan pertama yang jelas.')
        ->and($fresh->validated_by)->toBe($firstAdmin->id);
});

it('forbids an admin from rejecting their own listing', function () {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create(['seller_id' => $admin->id]);

    expect(fn () => app(RejectListing::class)->handle($admin, $listing, 'Alasan yang jelas.'))
        ->toThrow(ListingModerationException::class);

    expect($listing->fresh()->status)->toBe(ListingStatus::PendingReview);
});
