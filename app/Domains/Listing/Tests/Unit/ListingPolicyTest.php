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
 * Guests and other users may open photos only for photo-public statuses
 * (active, in_inspection, in_transaction, sold [USULAN]). Owner and admin
 * may always open photos, except when blocked or non-active.
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
        $dataset["{$status->value} guest ".($isPhotoPublic ? 'allowed' : 'denied')] = [$status, 'guest', $isPhotoPublic];
        $dataset["{$status->value} owner allowed"] = [$status, 'owner', true];
        $dataset["{$status->value} admin allowed"] = [$status, 'admin', true];
        $dataset["{$status->value} other user ".($isPhotoPublic ? 'allowed' : 'denied')] = [$status, 'other', $isPhotoPublic];
        $dataset["{$status->value} inspector ".($isPhotoPublic ? 'allowed' : 'denied')] = [$status, 'inspector', $isPhotoPublic];
        $dataset["{$status->value} blocked owner denied"] = [$status, 'blocked_owner', false];
        $dataset["{$status->value} blocked admin denied"] = [$status, 'blocked_admin', false];
        $dataset["{$status->value} non-active user denied"] = [$status, 'pending_user', false];
    }

    return $dataset;
}

it('LST-T17 controls photo viewing for every status and role', function (ListingStatus $status, string $role, bool $allowed) {
    $owner = User::factory()->user()->create();
    $blockedOwner = User::factory()->user()->blocked()->create();

    $listing = Listing::factory()->create(['seller_id' => $owner->id, 'status' => $status]);
    $blockedListing = Listing::factory()->create(['seller_id' => $blockedOwner->id, 'status' => $status]);
    $photo = ListingPhoto::factory()->create(['listing_id' => $listing->id]);
    $blockedPhoto = ListingPhoto::factory()->create(['listing_id' => $blockedListing->id]);

    [$actor, $targetListing, $targetPhoto] = match ($role) {
        'guest' => [null, $listing, $photo],
        'owner' => [$owner, $listing, $photo],
        'admin' => [User::factory()->admin()->create(), $listing, $photo],
        'other' => [User::factory()->user()->create(), $listing, $photo],
        'inspector' => [User::factory()->inspector()->create(), $listing, $photo],
        'blocked_owner' => [$blockedOwner, $blockedListing, $blockedPhoto],
        'blocked_admin' => [User::factory()->admin()->blocked()->create(), $listing, $photo],
        'pending_user' => [User::factory()->user()->pending()->create(), $listing, $photo],
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
