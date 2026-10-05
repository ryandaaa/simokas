<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\ApproveListing;
use App\Domains\Listing\Actions\RejectListing;
use App\Domains\Listing\Actions\ResubmitListing;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingApproved;
use App\Domains\Listing\Events\ListingRejected;
use App\Domains\Listing\Events\ListingSubmitted;
use App\Domains\Listing\Http\Resources\ListingPublicResource;
use App\Domains\Listing\Models\Listing;
use App\Support\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\ListingPrivacyHelper;

uses(RefreshDatabase::class);

it('LST-T04 runs the full moderation lifecycle pending to active', function () {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create();

    app(ApproveListing::class)->handle($admin, $listing);

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});

it('LST-T04 runs the full moderation lifecycle pending to rejected', function () {
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create();

    app(RejectListing::class)->handle($admin, $listing, 'Nomor rangka tidak terbaca di foto.');

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Rejected)
        ->and($fresh->rejection_reason)->toBe('Nomor rangka tidak terbaca di foto.');
});

it('LST-T05 runs the full reject then resubmit lifecycle', function () {
    $seller = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create(['seller_id' => $seller->id]);

    app(RejectListing::class)->handle($admin, $listing, 'Deskripsi tidak sesuai kondisi.');
    expect($listing->fresh()->status)->toBe(ListingStatus::Rejected);

    app(ResubmitListing::class)->handle($seller, $listing->fresh());
    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::PendingReview)
        ->and($fresh->rejection_reason)->toBeNull();
});

it('LST-T16 concurrent admin actions only take effect once without double effect', function () {
    Event::fake([ListingApproved::class, ListingRejected::class]);

    $firstAdmin = User::factory()->admin()->create();
    $secondAdmin = User::factory()->admin()->create();
    $listing = Listing::factory()->pendingReview()->create();

    app(ApproveListing::class)->handle($firstAdmin, $listing);

    try {
        app(RejectListing::class)->handle($secondAdmin, $listing->fresh(), 'Alasan admin kedua.');
        expect(false)->toBeTrue('Second admin action should have failed.');
    } catch (DomainException $e) {
        expect($e->getMessage())->not->toBeEmpty();
    }

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Active)
        ->and($fresh->validated_by)->toBe($firstAdmin->id)
        ->and($fresh->rejection_reason)->toBeNull();

    Event::assertDispatchedTimes(ListingApproved::class, 1);
    Event::assertNotDispatched(ListingRejected::class);
});

it('keeps pending and rejected listings out of the public scope and public resource', function () {
    $pending = Listing::factory()->pendingReview()->create();
    $rejected = Listing::factory()->rejected('Alasan.')->create();
    $active = Listing::factory()->active()->create();

    $visibleIds = Listing::query()->publiclyVisible()->pluck('id')->all();

    expect($visibleIds)->not->toContain($pending->id)
        ->and($visibleIds)->not->toContain($rejected->id)
        ->and($visibleIds)->toContain($active->id);

    $request = Request::create('/listings/'.$pending->id);
    ListingPrivacyHelper::assertNoPrivateListingData(
        (new ListingPublicResource($pending))->toArray($request)
    );
});

it('dispatches moderation events exactly once per action', function () {
    Event::fake([ListingApproved::class, ListingRejected::class, ListingSubmitted::class]);

    $seller = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();

    $toApprove = Listing::factory()->pendingReview()->create();
    app(ApproveListing::class)->handle($admin, $toApprove);

    $toReject = Listing::factory()->pendingReview()->create();
    app(RejectListing::class)->handle($admin, $toReject, 'Alasan yang jelas.');

    $toResubmit = Listing::factory()->rejected('Alasan lama.')->create(['seller_id' => $seller->id]);
    app(ResubmitListing::class)->handle($seller, $toResubmit);

    Event::assertDispatchedTimes(ListingApproved::class, 1);
    Event::assertDispatchedTimes(ListingRejected::class, 1);
    Event::assertDispatchedTimes(ListingSubmitted::class, 1);
});
