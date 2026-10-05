<?php

declare(strict_types=1);

use App\Domains\Listing\Actions\MarkListingInInspection;
use App\Domains\Listing\Actions\MarkListingInspected;
use App\Domains\Listing\Actions\ReturnListingToActive;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingInspected;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('marks an active listing in_inspection and stays idempotent', function () {
    $listing = Listing::factory()->active()->create();

    $result = app(MarkListingInInspection::class)->handle($listing);
    expect($result->fresh()->status)->toBe(ListingStatus::InInspection);

    $again = app(MarkListingInInspection::class)->handle($listing->fresh());
    expect($again->fresh()->status)->toBe(ListingStatus::InInspection);
});

it('refuses to mark a non-active listing in_inspection', function () {
    $pending = Listing::factory()->pendingReview()->create();
    expect(fn () => app(MarkListingInInspection::class)->handle($pending))
        ->toThrow(InvalidListingStatusTransitionException::class);
    expect($pending->fresh()->status)->toBe(ListingStatus::PendingReview);

    $sold = Listing::factory()->sold()->create();
    expect(fn () => app(MarkListingInInspection::class)->handle($sold))
        ->toThrow(InvalidListingStatusTransitionException::class);
});

it('returns an in_inspection listing to active and stays idempotent', function () {
    $listing = Listing::factory()->inInspection()->create();

    $result = app(ReturnListingToActive::class)->handle($listing);
    expect($result->fresh()->status)->toBe(ListingStatus::Active);

    $again = app(ReturnListingToActive::class)->handle($listing->fresh());
    expect($again->fresh()->status)->toBe(ListingStatus::Active);
});

it('refuses to return a non-inspection listing to active', function () {
    $pending = Listing::factory()->pendingReview()->create();
    expect(fn () => app(ReturnListingToActive::class)->handle($pending))
        ->toThrow(InvalidListingStatusTransitionException::class);
});

it('marks a listing inspected with badge and timestamp, idempotently', function () {
    $listing = Listing::factory()->inInspection()->create();
    $moment = now()->subHour()->startOfSecond();

    Event::fake([ListingInspected::class]);

    $result = app(MarkListingInspected::class)->handle($listing, $moment);

    $fresh = $result->fresh();
    expect($fresh->is_inspected)->toBeTrue()
        ->and($fresh->last_inspected_at?->equalTo($moment))->toBeTrue();

    Event::assertDispatched(ListingInspected::class, fn (ListingInspected $event): bool => $event->listingId === $listing->id);

    $again = app(MarkListingInspected::class)->handle($listing->fresh(), $moment);
    expect($again->fresh()->is_inspected)->toBeTrue()
        ->and($again->fresh()->last_inspected_at?->equalTo($moment))->toBeTrue();
});

it('defaults the inspection timestamp to now when not given', function () {
    $before = now()->subMinute()->startOfSecond();

    $listing = Listing::factory()->active()->create();

    app(MarkListingInspected::class)->handle($listing);

    $stamped = $listing->fresh()->last_inspected_at;

    expect($stamped)->not->toBeNull()
        ->and($stamped->greaterThanOrEqualTo($before))->toBeTrue();
});
