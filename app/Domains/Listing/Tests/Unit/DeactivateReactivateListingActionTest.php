<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\DeactivateListing;
use App\Domains\Listing\Actions\ReactivateListing;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingDeactivated;
use App\Domains\Listing\Events\ListingReactivated;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingLockedException;
use App\Domains\Listing\Exceptions\ListingModerationException;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Tests\Fakes\FakeListingActivityChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('deactivates an active listing and reactivates it without re-moderation', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $owner->id]);

    Event::fake([ListingDeactivated::class, ListingReactivated::class]);

    app(DeactivateListing::class)->handle($owner, $listing);

    expect($listing->fresh()->status)->toBe(ListingStatus::Inactive);
    Event::assertDispatched(ListingDeactivated::class);

    app(ReactivateListing::class)->handle($owner, $listing->fresh());

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Active)
        ->and($fresh->is_inspected)->toBeFalse();
    Event::assertDispatched(ListingReactivated::class);
});

it('keeps the inspection badge when deactivating and reactivating', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->inspected()->create(['seller_id' => $owner->id]);

    app(DeactivateListing::class)->handle($owner, $listing);
    expect($listing->fresh()->is_inspected)->toBeTrue();

    app(ReactivateListing::class)->handle($owner, $listing->fresh());
    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Active)
        ->and($fresh->is_inspected)->toBeTrue()
        ->and($fresh->last_inspected_at)->not->toBeNull();
});

it('LST-T09 refuses to deactivate a listing with an active job or transaction (LST-E4)', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $owner->id]);

    $checker = new FakeListingActivityChecker;
    $checker->withActiveJob($listing->id);
    app()->instance(ListingActivityChecker::class, $checker);

    expect(fn () => app(DeactivateListing::class)->handle($owner, $listing))
        ->toThrow(ListingLockedException::class);
    expect($listing->fresh()->status)->toBe(ListingStatus::Active);

    $checker->withActiveTransaction($listing->id);

    expect(fn () => app(DeactivateListing::class)->handle($owner, $listing))
        ->toThrow(ListingLockedException::class);
    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('refuses deactivate and reactivate on the wrong status', function () {
    $owner = User::factory()->user()->create();

    $pending = Listing::factory()->pendingReview()->create(['seller_id' => $owner->id]);
    expect(fn () => app(DeactivateListing::class)->handle($owner, $pending))
        ->toThrow(InvalidListingStatusTransitionException::class);

    $sold = Listing::factory()->sold()->create(['seller_id' => $owner->id]);
    expect(fn () => app(DeactivateListing::class)->handle($owner, $sold))
        ->toThrow(InvalidListingStatusTransitionException::class);

    $active = Listing::factory()->active()->create(['seller_id' => $owner->id]);
    expect(fn () => app(ReactivateListing::class)->handle($owner, $active))
        ->toThrow(InvalidListingStatusTransitionException::class);

    $rejected = Listing::factory()->rejected()->create(['seller_id' => $owner->id]);
    expect(fn () => app(ReactivateListing::class)->handle($owner, $rejected))
        ->toThrow(InvalidListingStatusTransitionException::class);
});

it('fails deactivate and reactivate without effect when the status already changed', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $owner->id]);

    Listing::query()->whereKey($listing->id)->update(['status' => ListingStatus::Inactive->value]);

    expect(fn () => app(DeactivateListing::class)->handle($owner, $listing))
        ->toThrow(InvalidListingStatusTransitionException::class);
    expect($listing->fresh()->status)->toBe(ListingStatus::Inactive);

    Listing::query()->whereKey($listing->id)->update(['status' => ListingStatus::Active->value]);

    $staleInactive = $listing->fresh();
    Listing::query()->whereKey($listing->id)->update(['status' => ListingStatus::PendingReview->value]);

    expect(fn () => app(ReactivateListing::class)->handle($owner, $staleInactive))
        ->toThrow(InvalidListingStatusTransitionException::class);
    expect($listing->fresh()->status)->toBe(ListingStatus::PendingReview);
});

it('lets admin deactivate and reactivate but forbids other users', function () {
    $owner = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $other = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $owner->id]);

    expect(fn () => app(DeactivateListing::class)->handle($other, $listing))
        ->toThrow(ListingModerationException::class);
    expect($listing->fresh()->status)->toBe(ListingStatus::Active);

    app(DeactivateListing::class)->handle($admin, $listing);
    expect($listing->fresh()->status)->toBe(ListingStatus::Inactive);

    expect(fn () => app(ReactivateListing::class)->handle($other, $listing->fresh()))
        ->toThrow(ListingModerationException::class);

    app(ReactivateListing::class)->handle($admin, $listing->fresh());
    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});
