<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Http\Resources\ListingPublicResource;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Policies\ListingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\ListingPrivacyHelper;

uses(RefreshDatabase::class);

it('LST-T13 only lets owner and admin open non-public listings (LST-E10)', function (ListingStatus $status, string $role, bool $allowed) {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->create(['seller_id' => $owner->id, 'status' => $status]);

    $actor = match ($role) {
        'guest' => null,
        'other' => User::factory()->user()->create(),
        'inspector' => User::factory()->inspector()->create(),
        'owner' => $owner,
        'admin' => User::factory()->admin()->create(),
        'blocked_owner' => tap($owner, fn () => $owner->update(['status' => UserStatus::Blocked])),
        default => throw new InvalidArgumentException("Unhandled role: {$role}"),
    };

    $policy = app(ListingPolicy::class);

    expect($policy->view($actor, $listing->fresh()))->toBe($allowed);

    if ($actor !== null) {
        expect(Gate::forUser($actor)->allows('view', $listing->fresh()))->toBe($allowed);
    }
})->with([
    'active is public for guest' => [ListingStatus::Active, 'guest', true],
    'active is public for other user' => [ListingStatus::Active, 'other', true],
    'active is public for inspector' => [ListingStatus::Active, 'inspector', true],
    'pending_review hidden from guest' => [ListingStatus::PendingReview, 'guest', false],
    'pending_review hidden from other user' => [ListingStatus::PendingReview, 'other', false],
    'pending_review hidden from inspector' => [ListingStatus::PendingReview, 'inspector', false],
    'pending_review visible to owner' => [ListingStatus::PendingReview, 'owner', true],
    'pending_review visible to admin' => [ListingStatus::PendingReview, 'admin', true],
    'pending_review hidden from blocked owner' => [ListingStatus::PendingReview, 'blocked_owner', false],
    'rejected hidden from other user' => [ListingStatus::Rejected, 'other', false],
    'rejected visible to owner' => [ListingStatus::Rejected, 'owner', true],
    'rejected visible to admin' => [ListingStatus::Rejected, 'admin', true],
    'inactive hidden from other user' => [ListingStatus::Inactive, 'other', false],
    'inactive visible to owner' => [ListingStatus::Inactive, 'owner', true],
    'inactive visible to admin' => [ListingStatus::Inactive, 'admin', true],
    'sold hidden from other user' => [ListingStatus::Sold, 'other', false],
    'sold hidden from guest' => [ListingStatus::Sold, 'guest', false],
    'sold visible to owner' => [ListingStatus::Sold, 'owner', true],
    'sold visible to admin' => [ListingStatus::Sold, 'admin', true],
    'in_inspection hidden from other user' => [ListingStatus::InInspection, 'other', false],
    'in_inspection visible to owner' => [ListingStatus::InInspection, 'owner', true],
    'in_transaction hidden from other user' => [ListingStatus::InTransaction, 'other', false],
    'in_transaction visible to admin' => [ListingStatus::InTransaction, 'admin', true],
]);

it('LST-T13 forbids guests and strangers from opening photos of non-public listings', function (ListingStatus $status) {
    $owner = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $listing = Listing::factory()->create(['seller_id' => $owner->id, 'status' => $status]);
    $photo = $listing->photos()->create([
        'storage_path' => 'listings/'.$listing->id.'/cover.jpg',
        'sort_order' => 0,
        'is_cover' => true,
    ]);
    Storage::fake('private');
    Storage::disk('private')->put($photo->storage_path, 'bytes');

    $url = route('listings.photos.show', ['listing' => $listing->id, 'photo' => $photo->id]);

    \Pest\Laravel\get($url)->assertForbidden();

    \Pest\Laravel\actingAs($other);
    \Pest\Laravel\get($url)->assertForbidden();
})->with([
    [ListingStatus::PendingReview],
    [ListingStatus::Rejected],
    [ListingStatus::Inactive],
]);

it('LST-T17 allows guests and strangers to open photos of photo-public listings with a public cache header', function (ListingStatus $status) {
    $owner = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $listing = Listing::factory()->create(['seller_id' => $owner->id, 'status' => $status]);
    $photo = $listing->photos()->create([
        'storage_path' => 'listings/'.$listing->id.'/cover.jpg',
        'sort_order' => 0,
        'is_cover' => true,
    ]);
    Storage::fake('private');
    Storage::disk('private')->put($photo->storage_path, 'bytes');

    $url = route('listings.photos.show', ['listing' => $listing->id, 'photo' => $photo->id]);

    \Pest\Laravel\get($url)
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=86400, must-revalidate, public');

    \Pest\Laravel\actingAs($other);
    \Pest\Laravel\get($url)->assertOk();
})->with([
    [ListingStatus::Active],
    [ListingStatus::InInspection],
    [ListingStatus::InTransaction],
    [ListingStatus::Sold],
]);

it('LST-E11 renders a sold listing as sold without private data for later public pages', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->sold()->create([
        'seller_id' => $owner->id,
        'chassis_number' => 'MHKA1234567890123',
        'address_full' => 'Jl. Rahasia No. 1, Pekanbaru',
        'inspection_place' => 'Garasi rumah penjual',
    ]);

    $array = (new ListingPublicResource($listing))->toArray(Request::create('/'));

    expect($array['status'])->toBe(ListingStatus::Sold->value);
    ListingPrivacyHelper::assertNoPrivateListingData($array);
});
