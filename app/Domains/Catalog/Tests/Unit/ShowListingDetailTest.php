<?php

declare(strict_types=1);

use App\Domains\Catalog\Actions\ShowListingDetail;
use App\Domains\Catalog\Contracts\InspectionJobAccess;
use App\Domains\Identity\Models\User;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\ValueObjects\ApproximateLocation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeDetailListing(array $overrides = []): Listing
{
    return Listing::factory()->active()->create(array_merge([
        'lat' => 0.4851234,
        'lng' => 101.4012345,
        'address_full' => 'Jl. Detail Rahasia No. 8, Pekanbaru',
        'chassis_number' => 'MHKA8888888888888',
        'inspection_place' => 'Garasi rumah penjual, Jl. Detail Rahasia No. 8',
    ], $overrides));
}

it('CAT-T06 loads only safe columns for guests', function () {
    $listing = makeDetailListing();

    $detail = app(ShowListingDetail::class)->handle((string) $listing->getKey(), null);

    expect($detail->id)->toBe((string) $listing->getKey())
        ->and($detail->canSeePrivate)->toBeFalse()
        ->and($detail->addressFull)->toBeNull()
        ->and($detail->chassisNumber)->toBeNull()
        ->and($detail->inspectionPlace)->toBeNull()
        ->and($detail->exactLat)->toBeNull()
        ->and($detail->exactLng)->toBeNull()
        ->and($detail->approximate)->not->toBeNull();
});

it('CAT-T06 derives the approximate point without exposing the exact pin', function () {
    $listing = makeDetailListing(['lat' => 0.4851234, 'lng' => 101.4012345]);

    $detail = app(ShowListingDetail::class)->handle((string) $listing->getKey(), null);

    $expected = ApproximateLocation::fromExact(0.4851234, 101.4012345);

    expect($detail->approximate?->lat)->toEqual($expected->lat)
        ->and($detail->approximate?->lng)->toEqual($expected->lng)
        ->and([$detail->approximate?->lat, $detail->approximate?->lng])
        ->not->toEqual([0.4851234, 101.4012345]);
});

it('CAT-T07 grants exact data to the owner and admin only by default', function (string $role, bool $expected) {
    $owner = User::factory()->user()->create();
    $listing = makeDetailListing(['seller_id' => $owner->id]);

    $viewer = match ($role) {
        'owner' => $owner,
        'admin' => User::factory()->admin()->create(),
        'other' => User::factory()->user()->create(),
        'inspector' => User::factory()->inspector()->create(),
        default => null,
    };

    $detail = app(ShowListingDetail::class)->handle((string) $listing->getKey(), $viewer);

    expect($detail->canSeePrivate)->toBe($expected);

    if ($expected) {
        expect($detail->addressFull)->toContain('Jl. Detail Rahasia')
            ->and($detail->chassisNumber)->not->toBeNull()
            ->and($detail->inspectionPlace)->not->toBeNull();
    } else {
        expect($detail->addressFull)->toBeNull()
            ->and($detail->chassisNumber)->toBeNull()
            ->and($detail->inspectionPlace)->toBeNull();
    }
})->with([
    'owner sees private' => ['owner', true],
    'admin sees private' => ['admin', true],
    'other user does not' => ['other', false],
    'inspector without job does not' => ['inspector', false],
    'guest does not' => ['guest', false],
]);

it('CAT-T07 grants exact data to the job inspector through the seam', function () {
    $owner = User::factory()->user()->create();
    $inspector = User::factory()->inspector()->create();
    $listing = makeDetailListing(['seller_id' => $owner->id]);

    app()->bind(InspectionJobAccess::class, fn () => new class implements InspectionJobAccess
    {
        public function canViewPrivate(?User $viewer, Listing $listing): bool
        {
            return $viewer !== null && $viewer->isInspector();
        }
    });

    $detail = app(ShowListingDetail::class)->handle((string) $listing->getKey(), $inspector);

    expect($detail->canSeePrivate)->toBeTrue()
        ->and($detail->addressFull)->toContain('Jl. Detail Rahasia');
});

it('CAT-T08 hides non-public listings from unauthorized viewers', function (string $status) {
    $owner = User::factory()->user()->create();
    $other = User::factory()->user()->create();
    $listing = makeDetailListing(['seller_id' => $owner->id, 'status' => $status]);

    $action = app(ShowListingDetail::class);

    expect(fn () => $action->handle((string) $listing->getKey(), null))->toThrow(ModelNotFoundException::class);
    expect(fn () => $action->handle((string) $listing->getKey(), $other))->toThrow(ModelNotFoundException::class);

    expect($action->handle((string) $listing->getKey(), $owner)->canSeePrivate)->toBeTrue();
})->with(['pending_review', 'rejected', 'inactive']);

it('CAT-T08 shows sold listings publicly as sold without private data', function () {
    $listing = makeDetailListing(['status' => 'sold', 'is_inspected' => false]);

    $detail = app(ShowListingDetail::class)->handle((string) $listing->getKey(), null);

    expect($detail->status->value)->toBe('sold')
        ->and($detail->canSeePrivate)->toBeFalse();
});

it('CAT-T08 throws not found for unknown or invalid listing ids', function () {
    $action = app(ShowListingDetail::class);

    expect(fn () => $action->handle('00000000-0000-0000-0000-000000000000', null))->toThrow(ModelNotFoundException::class);
    expect(fn () => $action->handle('not-a-uuid', null))->toThrow(ModelNotFoundException::class);
});

it('CAT-T09 exposes only badge state from the action, badge only when inspected', function () {
    $badged = makeDetailListing(['is_inspected' => true, 'last_inspected_at' => now()]);
    $plain = makeDetailListing(['is_inspected' => false, 'last_inspected_at' => null]);

    $action = app(ShowListingDetail::class);

    expect($action->handle((string) $badged->getKey(), null)->showInspectedBadge())->toBeTrue()
        ->and($action->handle((string) $plain->getKey(), null)->showInspectedBadge())->toBeFalse()
        ->and($action->handle((string) $plain->getKey(), null)->lastInspectedAt)->toBeNull();
});
