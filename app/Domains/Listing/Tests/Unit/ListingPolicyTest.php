<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingPhoto;
use App\Domains\Listing\Policies\ListingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('allows only active regular users to create listings', function () {
    $policy = new ListingPolicy;

    $user = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $admin = User::factory()->admin()->create();
    $blockedUser = User::factory()->user()->blocked()->create();

    expect($policy->create($user))->toBeTrue()
        ->and($policy->create($inspector))->toBeFalse()
        ->and($policy->create($admin))->toBeFalse()
        ->and($policy->create($blockedUser))->toBeFalse();
});

it('allows only owner and admin to update listings', function () {
    $policy = new ListingPolicy;

    $owner = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $admin = User::factory()->admin()->create();
    $blockedOwner = User::factory()->user()->blocked()->create();

    $listing = Listing::factory()->create(['seller_id' => $owner->id]);
    $blockedListing = Listing::factory()->create(['seller_id' => $blockedOwner->id]);

    expect($policy->update($owner, $listing))->toBeTrue()
        ->and($policy->update($admin, $listing))->toBeTrue()
        ->and($policy->update($otherUser, $listing))->toBeFalse()
        ->and($policy->update($inspector, $listing))->toBeFalse()
        ->and($policy->update($blockedOwner, $blockedListing))->toBeFalse();
});

it('controls public and private listing visibility according to status and role', function () {
    $policy = new ListingPolicy;

    $owner = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();

    $activeListing = Listing::factory()->active()->create(['seller_id' => $owner->id]);
    $pendingListing = Listing::factory()->pendingReview()->create(['seller_id' => $owner->id]);
    $rejectedListing = Listing::factory()->rejected()->create(['seller_id' => $owner->id]);

    // Active listing: anyone can view
    expect($policy->view(null, $activeListing))->toBeTrue()
        ->and($policy->view($otherUser, $activeListing))->toBeTrue()
        ->and($policy->view($owner, $activeListing))->toBeTrue()
        ->and($policy->view($admin, $activeListing))->toBeTrue();

    // Pending review listing: only owner and admin
    expect($policy->view(null, $pendingListing))->toBeFalse()
        ->and($policy->view($otherUser, $pendingListing))->toBeFalse()
        ->and($policy->view($owner, $pendingListing))->toBeTrue()
        ->and($policy->view($admin, $pendingListing))->toBeTrue();

    // Rejected listing: only owner and admin
    expect($policy->view(null, $rejectedListing))->toBeFalse()
        ->and($policy->view($otherUser, $rejectedListing))->toBeFalse()
        ->and($policy->view($owner, $rejectedListing))->toBeTrue()
        ->and($policy->view($admin, $rejectedListing))->toBeTrue();

    // Private details: only owner and admin
    expect($policy->viewPrivate($owner, $activeListing))->toBeTrue()
        ->and($policy->viewPrivate($admin, $activeListing))->toBeTrue()
        ->and($policy->viewPrivate($otherUser, $activeListing))->toBeFalse();
});

it('controls photo viewing permissions according to listing status', function () {
    $policy = new ListingPolicy;

    $owner = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();

    $activeListing = Listing::factory()->active()->create(['seller_id' => $owner->id]);
    $pendingListing = Listing::factory()->pendingReview()->create(['seller_id' => $owner->id]);

    $activePhoto = ListingPhoto::factory()->create(['listing_id' => $activeListing->id]);
    $pendingPhoto = ListingPhoto::factory()->create(['listing_id' => $pendingListing->id]);

    // Active photo: guest and other users allowed
    expect($policy->viewPhoto(null, $activeListing, $activePhoto))->toBeTrue()
        ->and($policy->viewPhoto($otherUser, $activeListing, $activePhoto))->toBeTrue();

    // Pending photo: only owner and admin allowed
    expect($policy->viewPhoto(null, $pendingListing, $pendingPhoto))->toBeFalse()
        ->and($policy->viewPhoto($otherUser, $pendingListing, $pendingPhoto))->toBeFalse()
        ->and($policy->viewPhoto($owner, $pendingListing, $pendingPhoto))->toBeTrue()
        ->and($policy->viewPhoto($admin, $pendingListing, $pendingPhoto))->toBeTrue();
});

it('allows only the owner to resubmit rejected listings', function () {
    $policy = new ListingPolicy;

    $owner = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $rejected = Listing::factory()->rejected()->create(['seller_id' => $owner->id]);
    $pending = Listing::factory()->pendingReview()->create(['seller_id' => $owner->id]);

    expect($policy->resubmit($owner, $rejected))->toBeTrue()
        ->and($policy->resubmit($other, $rejected))->toBeFalse()
        ->and($policy->resubmit($admin, $rejected))->toBeFalse()
        ->and($policy->resubmit($owner, $pending))->toBeFalse();
});

it('grants listing.moderate gate only to active admins', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $blockedAdmin = User::factory()->admin()->blocked()->create();

    $gate = Gate::class;

    expect($gate::forUser($admin)->allows('listing.moderate'))->toBeTrue()
        ->and($gate::forUser($user)->allows('listing.moderate'))->toBeFalse()
        ->and($gate::forUser($inspector)->allows('listing.moderate'))->toBeFalse()
        ->and($gate::forUser($blockedAdmin)->allows('listing.moderate'))->toBeFalse();
});
