<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
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

/**
 * Photo access matrix for LST-T17 (docs/flows/listing.md).
 * Photos of photo-public statuses (active, in_inspection, in_transaction,
 * sold [USULAN]) may be opened by anyone, including guests and users of any
 * account status (active, pending, blocked). Photos of pending_review,
 * rejected, and inactive listings stay restricted to the active owner and
 * active admins; blocked or non-active viewers are denied and guests denied.
 *
 * @return array<string, array{ListingStatus, string, bool}>
 */
function photoAccessDataset(): array
{
    $photoPublic = [
        ListingStatus::Active,
        ListingStatus::InInspection,
        ListingStatus::InTransaction,
        ListingStatus::Sold,
    ];

    $dataset = [];

    foreach (ListingStatus::cases() as $status) {
        $isPhotoPublic = in_array($status, $photoPublic, true);
        // Public statuses: everyone allowed. Non-public: only active owner
        // and active admin allowed.
        $dataset["{$status->value} guest ".($isPhotoPublic ? 'allowed' : 'denied')] = [$status, 'guest', $isPhotoPublic];
        $dataset["{$status->value} owner active allowed"] = [$status, 'owner_active', true];
        $dataset["{$status->value} owner pending ".($isPhotoPublic ? 'allowed' : 'denied')] = [$status, 'owner_pending', $isPhotoPublic];
        $dataset["{$status->value} admin allowed"] = [$status, 'admin', true];
        $dataset["{$status->value} other active ".($isPhotoPublic ? 'allowed' : 'denied')] = [$status, 'other_active', $isPhotoPublic];
        $dataset["{$status->value} inspector pending ".($isPhotoPublic ? 'allowed' : 'denied')] = [$status, 'inspector_pending', $isPhotoPublic];
        $dataset["{$status->value} inspector approved ".($isPhotoPublic ? 'allowed' : 'denied')] = [$status, 'inspector_approved', $isPhotoPublic];
        $dataset["{$status->value} blocked ".($isPhotoPublic ? 'allowed' : 'denied')] = [$status, 'blocked', $isPhotoPublic];
        $dataset["{$status->value} inactive ".($isPhotoPublic ? 'allowed' : 'denied')] = [$status, 'inactive', $isPhotoPublic];
    }

    return $dataset;
}

it('LST-T17 controls photo viewing for every status and role', function (ListingStatus $status, string $role, bool $allowed) {
    $owner = User::factory()->user()->create();
    $pendingOwner = User::factory()->user()->pending()->create();
    $blockedUser = User::factory()->user()->blocked()->create();

    $listing = Listing::factory()->create(['seller_id' => $owner->id, 'status' => $status]);
    $pendingListing = Listing::factory()->create(['seller_id' => $pendingOwner->id, 'status' => $status]);
    $photo = ListingPhoto::factory()->create(['listing_id' => $listing->id]);
    $pendingPhoto = ListingPhoto::factory()->create(['listing_id' => $pendingListing->id]);

    [$actor, $targetListing, $targetPhoto] = match ($role) {
        'guest' => [null, $listing, $photo],
        'owner_active' => [$owner, $listing, $photo],
        'owner_pending' => [$pendingOwner, $pendingListing, $pendingPhoto],
        'admin' => [User::factory()->admin()->create(), $listing, $photo],
        'other_active' => [User::factory()->user()->create(), $listing, $photo],
        'inspector_pending' => [User::factory()->inspector()->pending()->create(), $listing, $photo],
        'inspector_approved' => [User::factory()->inspector()->create(), $listing, $photo],
        'blocked' => [$blockedUser, $listing, $photo],
        'inactive' => [User::factory()->user()->pending()->create(), $listing, $photo],
        default => throw new InvalidArgumentException("Unhandled role: {$role}"),
    };

    $policy = new ListingPolicy;

    expect($policy->viewPhoto($actor, $targetListing, $targetPhoto))->toBe($allowed);
})->with(fn (): array => photoAccessDataset());

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
