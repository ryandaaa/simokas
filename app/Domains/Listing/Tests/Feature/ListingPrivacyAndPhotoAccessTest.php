<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Http\Resources\ListingPrivateResource;
use App\Domains\Listing\Http\Resources\ListingPublicResource;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\ListingPrivacyHelper;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
});

it('LST-T11 ensures public listing resource never contains lat, lng, address_full, chassis_number, inspection_place', function () {
    $seller = User::factory()->user()->create();

    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'lat' => 0.5071000,
        'lng' => 101.4478000,
        'address_full' => 'Jl. Tuanku Tambusai No. 123, Pekanbaru',
        'chassis_number' => 'MHKA1234567890123',
        'inspection_place' => 'Jl. Tuanku Tambusai No. 123, rumah penjual',
    ]);

    $request = Request::create('/listings/'.$listing->id);
    $publicResource = new ListingPublicResource($listing);
    $responseArray = $publicResource->toArray($request);

    // Assert via custom helper
    ListingPrivacyHelper::assertNoPrivateListingData($responseArray);

    // Verify allowed public fields are present
    expect($responseArray)->toHaveKey('id')
        ->and($responseArray)->toHaveKey('title')
        ->and($responseArray)->toHaveKey('brand')
        ->and($responseArray)->toHaveKey('model')
        ->and($responseArray)->toHaveKey('year')
        ->and($responseArray)->toHaveKey('price')
        ->and($responseArray)->toHaveKey('odometer_km')
        ->and($responseArray)->toHaveKey('transmission')
        ->and($responseArray)->toHaveKey('fuel')
        ->and($responseArray)->toHaveKey('color')
        ->and($responseArray)->toHaveKey('area_name')
        ->and($responseArray)->toHaveKey('status');

    // Double check forbidden keys strictly
    expect($responseArray)->not->toHaveKey('lat')
        ->and($responseArray)->not->toHaveKey('lng')
        ->and($responseArray)->not->toHaveKey('address_full')
        ->and($responseArray)->not->toHaveKey('chassis_number')
        ->and($responseArray)->not->toHaveKey('inspection_place');
});

it('includes private fields in ListingPrivateResource for owner and admin', function () {
    $seller = User::factory()->user()->create();

    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'lat' => 0.5071000,
        'lng' => 101.4478000,
        'address_full' => 'Jl. Tuanku Tambusai No. 123, Pekanbaru',
        'chassis_number' => 'MHKA1234567890123',
    ]);

    $request = Request::create('/listings/'.$listing->id);
    $privateResource = new ListingPrivateResource($listing);
    $responseArray = $privateResource->toArray($request);

    expect($responseArray)->toHaveKey('lat')
        ->and($responseArray)->toHaveKey('lng')
        ->and($responseArray)->toHaveKey('address_full')
        ->and($responseArray)->toHaveKey('chassis_number')
        ->and($responseArray['chassis_number'])->toBe('MHKA1234567890123')
        ->and($responseArray)->toHaveKey('inspection_place');
});

it('hides inspection_place containing a full home address from public JSON and HTML, but keeps it for owner and admin', function () {
    $seller = User::factory()->user()->create();
    $homeAddress = 'Jl. Melati No. 8 RT 02, rumah penjual di Pekanbaru';

    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'inspection_place' => $homeAddress,
    ]);

    // Public JSON: array form and rendered JSON string must not leak it
    $request = Request::create('/listings/'.$listing->id);
    $publicArray = (new ListingPublicResource($listing))->toArray($request);
    ListingPrivacyHelper::assertNoPrivateListingData($publicArray);
    expect($publicArray)->not->toHaveKey('inspection_place');

    $publicJson = (new ListingPublicResource($listing))->toJson();
    ListingPrivacyHelper::assertNoPrivateListingData($publicJson);
    expect($publicJson)->not->toContain($homeAddress);

    // Public HTML: guests are redirected to login and see no address
    $guestResponse = get(route('listings.mine'));
    $guestResponse->assertRedirect(route('login'));
    expect($guestResponse->getContent())->not->toContain($homeAddress);

    // Private JSON: owner and admin still receive inspection_place
    $privateArray = (new ListingPrivateResource($listing))->toArray($request);
    expect($privateArray)->toHaveKey('inspection_place')
        ->and($privateArray['inspection_place'])->toBe($homeAddress);
});

it('allows public to view photos of active listing with nosniff and public cache headers', function () {
    $seller = User::factory()->user()->create();
    $listing = Listing::factory()->active()->create(['seller_id' => $seller->id]);

    $filePath = 'listings/'.$listing->id.'/cover.jpg';
    Storage::disk('private')->put($filePath, 'fake image binary content');

    $photo = ListingPhoto::factory()->create([
        'listing_id' => $listing->id,
        'storage_path' => $filePath,
        'is_cover' => true,
    ]);

    $response = get(route('listings.photos.show', ['listing' => $listing->id, 'photo' => $photo->id]));

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'max-age=86400, must-revalidate, public');
});

it('forbids public, guests, and other users from viewing photos of pending_review or rejected listing', function () {
    $owner = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();

    $pendingListing = Listing::factory()->pendingReview()->create(['seller_id' => $owner->id]);
    $filePath = 'listings/'.$pendingListing->id.'/cover.jpg';
    Storage::disk('private')->put($filePath, 'fake image binary content');

    $photo = ListingPhoto::factory()->create([
        'listing_id' => $pendingListing->id,
        'storage_path' => $filePath,
    ]);

    $photoUrl = route('listings.photos.show', ['listing' => $pendingListing->id, 'photo' => $photo->id]);

    // Guest -> 403
    get($photoUrl)->assertForbidden();

    // Other user -> 403
    actingAs($otherUser);
    get($photoUrl)->assertForbidden();

    // Inspector -> 403
    actingAs($inspector);
    get($photoUrl)->assertForbidden();
});

it('allows owner and admin to view photos of non-active listing with private cache headers', function () {
    $owner = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();

    $pendingListing = Listing::factory()->pendingReview()->create(['seller_id' => $owner->id]);
    $filePath = 'listings/'.$pendingListing->id.'/cover.jpg';
    Storage::disk('private')->put($filePath, 'fake image binary content');

    $photo = ListingPhoto::factory()->create([
        'listing_id' => $pendingListing->id,
        'storage_path' => $filePath,
    ]);

    $photoUrl = route('listings.photos.show', ['listing' => $pendingListing->id, 'photo' => $photo->id]);

    // Owner -> 200 with private cache
    actingAs($owner);
    get($photoUrl)
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');

    // Admin -> 200 with private cache
    actingAs($admin);
    get($photoUrl)
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
});
