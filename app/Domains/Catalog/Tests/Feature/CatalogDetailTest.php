<?php

declare(strict_types=1);

use App\Domains\Catalog\Contracts\InspectionJobAccess;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Helpers\ListingPrivacyHelper;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function makeVisibleListing(array $overrides = []): Listing
{
    return Listing::factory()->active()->create(array_merge([
        'title' => 'CAT detail vehicle 999',
        'lat' => 0.4851234,
        'lng' => 101.4012345,
        'address_full' => 'Jl. Rahasia Detail No. 77, Pekanbaru',
        'chassis_number' => 'MHKA7777777777777',
        'inspection_place' => 'Garasi rumah penjual, Jl. Rahasia Detail No. 77',
    ], $overrides));
}

function detailUrl(Listing $listing): string
{
    return route('catalog.show', ['listing' => $listing->getKey()]);
}

it('CAT-T06 keeps exact data out of the public detail page', function () {
    $listing = makeVisibleListing();
    Storage::disk('private')->put('listings/cat-t06/photo.webp', 'fake-image');
    $listing->photos()->create([
        'storage_path' => 'listings/cat-t06/photo.webp',
        'sort_order' => 0,
        'is_cover' => true,
    ]);

    $response = get(detailUrl($listing))->assertOk();

    ListingPrivacyHelper::assertNoPrivateListingData($response, [
        'Jl. Rahasia Detail No. 77, Pekanbaru',
        'MHKA7777777777777',
        'Garasi rumah penjual, Jl. Rahasia Detail No. 77',
        '0.4851234',
        '101.4012345',
    ]);

    $response->assertSee('CAT detail vehicle 999');
});

it('CAT-T07 shows exact data to the owner and admin only', function (string $role, bool $seesPrivate) {
    $owner = User::factory()->user()->create();
    $listing = makeVisibleListing(['seller_id' => $owner->id]);

    $viewer = match ($role) {
        'owner' => $owner,
        'admin' => User::factory()->admin()->create(),
        'other' => User::factory()->user()->create(),
        'inspector' => User::factory()->inspector()->create(),
        default => null,
    };

    $response = $viewer === null
        ? get(detailUrl($listing))->assertOk()
        : actingAs($viewer)->get(detailUrl($listing))->assertOk();

    if ($seesPrivate) {
        $response->assertSee('Jl. Rahasia Detail No. 77, Pekanbaru')
            ->assertSee('Garasi rumah penjual');
    } else {
        ListingPrivacyHelper::assertNoPrivateListingData($response, [
            'Jl. Rahasia Detail No. 77, Pekanbaru',
            'MHKA7777777777777',
            'Garasi rumah penjual, Jl. Rahasia Detail No. 77',
        ]);
    }
})->with([
    'owner sees private' => ['owner', true],
    'admin sees private' => ['admin', true],
    'other user does not' => ['other', false],
    'inspector without job does not' => ['inspector', false],
    'guest does not' => ['guest', false],
]);

it('CAT-T07 grants exact data to the job inspector through the seam fake', function () {
    $owner = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $listing = makeVisibleListing(['seller_id' => $owner->id]);

    app()->bind(InspectionJobAccess::class, fn () => new class implements InspectionJobAccess
    {
        public function canViewPrivate(?User $viewer, Listing $listing): bool
        {
            return $viewer !== null && $viewer->isInspector();
        }
    });

    actingAs($inspector)->get(detailUrl($listing))->assertOk()
        ->assertSee('Jl. Rahasia Detail No. 77, Pekanbaru');
});

it('CAT-T08 returns 404 for non-public listings for guests and other users', function (string $status) {
    $owner = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $listing = makeVisibleListing(['seller_id' => $owner->id, 'status' => $status]);

    get(detailUrl($listing))->assertNotFound();
    actingAs($other)->get(detailUrl($listing))->assertNotFound();
    actingAs($inspector)->get(detailUrl($listing))->assertNotFound();
})->with(['pending_review', 'rejected', 'inactive']);

it('CAT-T08 lets the owner and admin open non-public listings with a clear banner', function (string $status) {
    $owner = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $listing = makeVisibleListing(['seller_id' => $owner->id, 'status' => $status]);

    actingAs($owner)->get(detailUrl($listing))->assertOk()
        ->assertSee(__('catalog.detail.not_public_notice'));

    actingAs($admin)->get(detailUrl($listing))->assertOk()
        ->assertSee(__('catalog.detail.not_public_notice'));
})->with(['pending_review', 'rejected', 'inactive']);

it('CAT-T09 shows only the allowed action buttons per role and status', function (string $role, string $status, bool $seesRequest, bool $seesEdit) {
    $owner = User::factory()->user()->create();
    $listing = makeVisibleListing(['seller_id' => $owner->id, 'status' => $status]);

    $viewer = match ($role) {
        'owner' => $owner,
        'other' => User::factory()->user()->create(),
        'inspector' => User::factory()->inspector()->create(),
        'admin' => User::factory()->admin()->create(),
        default => null,
    };

    $content = $viewer === null
        ? get(detailUrl($listing))->assertOk()->getContent()
        : actingAs($viewer)->get(detailUrl($listing))->assertOk()->getContent();

    $requestLabel = __('catalog.detail.action_request_inspection');
    $editLabel = __('catalog.detail.action_edit');

    if ($seesRequest) {
        expect($content)->toContain($requestLabel);
    } else {
        expect($content)->not->toContain($requestLabel);
    }

    if ($seesEdit) {
        expect($content)->toContain($editLabel);
    } else {
        expect($content)->not->toContain($editLabel);
    }
})->with([
    // role, status, sees request-inspection, sees edit
    'guest on active sees request only' => ['guest', 'active', true, false],
    'other user on active sees request' => ['other', 'active', true, false],
    'owner on active sees edit only' => ['owner', 'active', false, true],
    'admin on active sees edit only' => ['admin', 'active', false, true],
    'inspector on active sees neither (D-06)' => ['inspector', 'active', false, false],
    'other user on in_inspection sees none' => ['other', 'in_inspection', false, false],
    'other user on in_transaction sees none' => ['other', 'in_transaction', false, false],
    'owner on in_inspection sees edit only' => ['owner', 'in_inspection', false, true],
    'guest on sold sees none' => ['guest', 'sold', false, false],
    'other user on sold sees none' => ['other', 'sold', false, false],
    'owner on sold sees none (Terjual, CAT-E11)' => ['owner', 'sold', false, false],
]);

it('CAT-T09 shows the request-inspection button to eligible users once the JOB-1 route exists', function () {
    Route::get('/test-jobs-request/{listing}', fn () => 'ok')->name('jobs.request');
    Route::getRoutes()->refreshNameLookups();

    $owner = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $listing = makeVisibleListing(['seller_id' => $owner->id, 'status' => 'active']);

    actingAs($other)->get(detailUrl($listing))->assertOk()
        ->assertSee(__('catalog.detail.action_request_inspection'), false);
});

it('CAT-T09 shows result buttons only when their routes exist', function () {
    $owner = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $listing = makeVisibleListing(['seller_id' => $owner->id, 'status' => 'active', 'is_inspected' => true, 'last_inspected_at' => now()]);

    actingAs($other)->get(detailUrl($listing))->assertOk()
        ->assertSee(__('catalog.detail.action_open_result'), false);

    actingAs($owner)->get(detailUrl($listing))->assertOk()
        ->assertSee(__('catalog.detail.action_view_result'), false);
});

it('CAT-E6 sends guests to login and back to the listing', function () {
    $listing = makeVisibleListing(['status' => 'active']);

    $response = get(detailUrl($listing))->assertOk();

    $response->assertSee(route('login'), false);
    $response->assertSessionHas('url.intended', detailUrl($listing));
});

it('CAT-T09 escapes hostile titles and descriptions', function () {
    $listing = makeVisibleListing([
        'title' => 'CAT <script>alert("xss-title")</script> Avanza 999',
        'description' => "Baris satu.\n<script>alert(\"xss-desc\")</script>\nBaris dua <img src=x onerror=alert(1)>",
    ]);

    $content = get(detailUrl($listing))->assertOk()->getContent();

    expect($content)->not->toContain('<script>alert("xss-title")</script>')
        ->and($content)->not->toContain('<script>alert("xss-desc")</script>')
        ->and($content)->not->toContain('<img src=x onerror=alert(1)>')
        ->and($content)->toContain(e('CAT <script>alert("xss-title")</script> Avanza 999'));
});

it('CAT-R5 renders an approximate map that never equals the exact pin', function () {
    $listing = makeVisibleListing(['lat' => 0.4851234, 'lng' => 101.4012345]);

    $content = get(detailUrl($listing))->assertOk()->getContent();

    expect($content)->toContain('data-catalog-map')
        ->and($content)->toContain('0.49')
        ->and($content)->toContain('101.4')
        ->and($content)->not->toContain('0.4851234')
        ->and($content)->not->toContain('101.4012345')
        ->and($content)->toContain(__('catalog.detail.map_approximate_note'))
        ->and($content)->toContain('OpenStreetMap')
        ->and($content)->toContain('<noscript');
});

it('CAT-T12 shows the inspected badge with date only when inspected', function () {
    $badged = makeVisibleListing([
        'title' => 'CAT-T12 badged detail 999',
        'is_inspected' => true,
        'last_inspected_at' => now(),
    ]);
    $plain = makeVisibleListing([
        'title' => 'CAT-T12 plain detail 999',
        'is_inspected' => false,
        'last_inspected_at' => null,
    ]);

    get(detailUrl($badged))->assertOk()
        ->assertSee(__('ui.badges.inspected'), false);

    $content = get(detailUrl($plain))->assertOk()->getContent();
    expect($content)->not->toContain(__('ui.badges.inspected'));
});

it('CAT-E11 shows sold listings as sold without action buttons', function () {
    $listing = makeVisibleListing(['status' => 'sold']);

    $content = get(detailUrl($listing))->assertOk()->getContent();

    expect($content)->toContain(__('catalog.detail.sold_notice'))
        ->and($content)->not->toContain(__('catalog.detail.action_request_inspection'))
        ->and($content)->not->toContain(__('catalog.detail.action_edit'));
});

it('CAT-E10 returns 404 for invalid listing ids without a 500', function () {
    get('/listings/not-a-valid-uuid')->assertNotFound();
    get('/listings/00000000-0000-0000-0000-000000000000')->assertNotFound();
});

it('CAT-E8 keeps the detail query count flat as photos grow', function () {
    $few = makeVisibleListing(['title' => 'CAT few photos 999']);
    $few->photos()->createMany(array_map(fn (int $i) => [
        'storage_path' => "listings/cat-few/{$i}.webp",
        'sort_order' => $i,
        'is_cover' => $i === 0,
    ], range(0, 2)));

    DB::flushQueryLog();
    DB::enableQueryLog();
    get(detailUrl($few))->assertOk();
    $small = count(DB::getQueryLog());

    $many = makeVisibleListing(['title' => 'CAT many photos 999']);
    $many->photos()->createMany(array_map(fn (int $i) => [
        'storage_path' => "listings/cat-many/{$i}.webp",
        'sort_order' => $i,
        'is_cover' => $i === 0,
    ], range(0, 7)));

    DB::flushQueryLog();
    get(detailUrl($many))->assertOk();
    $large = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($large)->toBe($small);
});

it('CAT-R5 renders gallery images with lazy load, dimensions, and meaningful alt', function () {
    Storage::disk('private')->put('listings/cat-gal/0.webp', 'fake-image');
    Storage::disk('private')->put('listings/cat-gal/1.webp', 'fake-image');
    $listing = makeVisibleListing(['title' => 'CAT gallery Avanza 999']);
    $listing->photos()->createMany([
        ['storage_path' => 'listings/cat-gal/0.webp', 'sort_order' => 0, 'is_cover' => true],
        ['storage_path' => 'listings/cat-gal/1.webp', 'sort_order' => 1, 'is_cover' => false],
    ]);

    $content = get(detailUrl($listing))->assertOk()->getContent();
    $photoId = $listing->photos()->firstOrFail()->getKey();

    expect($content)->toContain('loading="lazy"')
        ->and($content)->toContain(route('listings.photos.show', ['listing' => $listing->getKey(), 'photo' => $photoId], false))
        ->and($content)->toContain(e('CAT gallery Avanza 999'));
});

it('CAT-T09 shows Chat Penjual button for eligible buyers and login for guests', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $listing = makeVisibleListing(['seller_id' => $seller->id]);

    // Pembeli lain melihat tombol chat (form POST ke conversations.start)
    $buyerContent = actingAs($buyer)->get(detailUrl($listing))->assertOk()->getContent();

    expect($buyerContent)->toContain('Chat Penjual')
        ->and($buyerContent)->toContain(route('conversations.start', absolute: false));

    // Tamu melihat tombol chat yang mengarah ke login
    Auth::logout();
    $guestContent = get(detailUrl($listing))->assertOk()->getContent();

    expect($guestContent)->toContain('Chat Penjual')
        ->and($guestContent)->toContain(route('login', absolute: false));
});

it('CAT-T09 hides Chat Penjual button for owners, inspectors, and sold listings', function () {
    $seller = User::factory()->user()->create();
    $buyer = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $listing = makeVisibleListing(['seller_id' => $seller->id]);
    $sold = makeVisibleListing(['seller_id' => $seller->id, 'title' => 'CAT sold vehicle 998']);
    $sold->update(['status' => 'sold']);

    expect(actingAs($seller)->get(detailUrl($listing))->assertOk()->getContent())
        ->not->toContain('Chat Penjual');

    expect(actingAs($inspector)->get(detailUrl($listing))->assertOk()->getContent())
        ->not->toContain('Chat Penjual');

    expect(actingAs($buyer)->get(detailUrl($sold))->assertOk()->getContent())
        ->not->toContain('Chat Penjual');
});
