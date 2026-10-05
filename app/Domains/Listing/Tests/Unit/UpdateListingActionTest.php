<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\UpdateListing;
use App\Domains\Listing\Contracts\ListingActivityChecker;
use App\Domains\Listing\DTOs\UpdateListingDTO;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Events\ListingUpdated;
use App\Domains\Listing\Exceptions\InvalidListingStatusTransitionException;
use App\Domains\Listing\Exceptions\ListingLockedException;
use App\Domains\Listing\Exceptions\ListingModerationException;
use App\Domains\Listing\Exceptions\ListingValidationException;
use App\Domains\Listing\Exceptions\PhotoUploadException;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Tests\Fakes\FakeListingActivityChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
});

function updateListingFakeImage(string $name = 'photo.jpg', int $sizeKilobytes = 50): UploadedFile
{
    return UploadedFile::fake()->image($name)->size($sizeKilobytes);
}

function activeListingForEdit(User $owner, array $overrides = []): Listing
{
    return Listing::factory()->active()->withPhotos(3)->create(array_merge(
        [
            'seller_id' => $owner->id,
            'year' => 2016,
            'odometer_km' => 50000,
            'price' => 100_000_000,
            'brand' => 'Toyota',
            'model' => 'Avanza',
            'vehicle_type' => 'car',
            'chassis_number' => 'MHKA1234567890123',
            'title' => 'Toyota Avanza 1.3 G 2018 Terawat',
            'color' => 'Putih',
            'description' => 'Kondisi kendaraan sangat terawat, servis rutin di bengkel resmi, surat lengkap.',
        ],
        $overrides
    ));
}

it('LST-T06 applies non-core edits directly without changing status', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner, [
        'is_inspected' => true,
        'last_inspected_at' => now()->subDays(2),
    ]);

    Event::fake([ListingUpdated::class]);

    $result = app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(fields: [
        'price' => 150_000_000,
        'negotiable' => false,
        'description' => 'Harga baru, kondisi tetap mulus dan terawat.',
        'color' => 'Hitam Metalik',
        'title' => 'Toyota Avanza 1.3 G Manual 2018 Istimewa',
        'inspection_place' => 'Bengkel mitra dekat rumah',
    ]));

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Active)
        ->and($fresh->price)->toBe(150_000_000)
        ->and($fresh->negotiable)->toBeFalse()
        ->and($fresh->description)->toBe('Harga baru, kondisi tetap mulus dan terawat.')
        ->and($fresh->color)->toBe('Hitam Metalik')
        ->and($fresh->is_inspected)->toBeTrue()
        ->and($fresh->last_inspected_at)->not->toBeNull()
        ->and($result->id)->toBe($listing->id);

    Event::assertDispatched(ListingUpdated::class, fn (ListingUpdated $event): bool => $event->listingId === $listing->id
        && $event->sentToReview === false);
});

it('LST-T06 does nothing when no value changes at all', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner);

    Event::fake([ListingUpdated::class]);

    $before = $listing->fresh()->updated_at;

    app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(fields: [
        'price' => $listing->price,
        'color' => $listing->color,
    ]));

    expect($listing->fresh()->status)->toBe(ListingStatus::Active)
        ->and($listing->fresh()->updated_at?->equalTo($before))->toBeTrue();

    Event::assertNotDispatched(ListingUpdated::class);
});

it('LST-T07 sends core field edits back to pending_review and revokes the badge (LST-E5)', function (string $field, mixed $value) {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner, [
        'is_inspected' => true,
        'last_inspected_at' => now()->subDays(2),
    ]);

    Event::fake([ListingUpdated::class]);

    app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(fields: [$field => $value]));

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::PendingReview)
        ->and($fresh->is_inspected)->toBeFalse()
        ->and($fresh->last_inspected_at)->toBeNull()
        ->and($fresh->validated_by)->toBeNull()
        ->and($fresh->validated_at)->toBeNull();

    Event::assertDispatched(ListingUpdated::class, fn (ListingUpdated $event): bool => $event->sentToReview === true);
})->with([
    'vehicle_type' => ['vehicle_type', 'motorcycle'],
    'brand' => ['brand', 'Honda'],
    'model' => ['model', 'Brio'],
    'year' => ['year', 1990],
    'chassis_number' => ['chassis_number', 'MHKA9999999999999'],
    'odometer_km' => ['odometer_km', 5],
]);

it('forbids edits by non-owners and blocked actors', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner, ['price' => 100_000_000]);
    $action = app(UpdateListing::class);
    $dto = new UpdateListingDTO(fields: ['price' => 123_000_000]);

    $other = User::factory()->user()->create();
    expect(fn () => $action->handle($other, $listing, $dto))->toThrow(ListingModerationException::class);

    $inspector = User::factory()->inspector()->create();
    expect(fn () => $action->handle($inspector, $listing, $dto))->toThrow(ListingModerationException::class);

    $blockedOwner = User::factory()->blocked()->user()->create();
    $blockedListing = activeListingForEdit($blockedOwner);
    expect(fn () => $action->handle($blockedOwner, $blockedListing, $dto))->toThrow(ListingValidationException::class);

    expect($listing->fresh()->price)->not->toBe(123_000_000);
});

it('allows admin to edit but never lets seller_id, status, or badge be manipulated', function () {
    $owner = User::factory()->user()->create();
    $admin = User::factory()->admin()->create();
    $impostor = User::factory()->user()->create();
    $listing = activeListingForEdit($owner);

    app(UpdateListing::class)->handle($admin, $listing, new UpdateListingDTO(fields: [
        'price' => 140_000_000,
        'seller_id' => $impostor->id,
        'status' => 'sold',
        'is_inspected' => true,
    ]));

    $fresh = $listing->fresh();
    expect($fresh->price)->toBe(140_000_000)
        ->and($fresh->seller_id)->toBe($owner->id)
        ->and($fresh->status)->toBe(ListingStatus::Active)
        ->and($fresh->is_inspected)->toBeFalse();
});

it('rejects any edit on sold listings (LST-R8)', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->sold()->create(['seller_id' => $owner->id]);

    expect(fn () => app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(fields: ['price' => 1])))
        ->toThrow(ListingLockedException::class);
});

it('rejects all edits while listing is in_transaction', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->inTransaction()->create(['seller_id' => $owner->id]);

    expect(fn () => app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(fields: ['description' => 'Coba ubah'])))
        ->toThrow(ListingLockedException::class);
});

it('rejects edits on pending_review, rejected, and inactive listings', function (string $state) {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->$state()->create(['seller_id' => $owner->id]);

    expect(fn () => app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(fields: ['price' => 1])))
        ->toThrow(InvalidListingStatusTransitionException::class);
})->with(['pendingReview', 'rejected', 'inactive']);

it('LST-T08 locks core fields when a job or transaction is active, but allows non-core edits', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner, [
        'odometer_km' => 50000,
        'price' => 100_000_000,
        'brand' => 'Toyota',
    ]);

    $checker = new FakeListingActivityChecker;
    $checker->withActiveJob($listing->id);
    app()->instance(ListingActivityChecker::class, $checker);

    $action = app(UpdateListing::class);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(fields: ['odometer_km' => 77777])))
        ->toThrow(ListingLockedException::class);

    $action->handle($owner, $listing, new UpdateListingDTO(fields: ['price' => 149_000_000]));

    expect($listing->fresh()->price)->toBe(149_000_000)
        ->and($listing->fresh()->status)->toBe(ListingStatus::Active);

    $checker->withActiveTransaction($listing->id);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(fields: ['brand' => 'Suzuki'])))
        ->toThrow(ListingLockedException::class);
});

it('LST-T08 locks core fields while listing is in_inspection', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->inInspection()->withPhotos(3)->create([
        'seller_id' => $owner->id,
        'year' => 2016,
        'description' => 'Deskripsi awal yang pasti berbeda.',
    ]);
    $action = app(UpdateListing::class);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(fields: ['year' => 2019])))
        ->toThrow(ListingLockedException::class);

    $action->handle($owner, $listing, new UpdateListingDTO(fields: ['description' => 'Tetap bisa ubah deskripsi.']));

    expect($listing->fresh()->description)->toBe('Tetap bisa ubah deskripsi.')
        ->and($listing->fresh()->status)->toBe(ListingStatus::InInspection);
});

it('fails without effect when the status already changed (conditional update)', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner);
    $originalPrice = $listing->price;

    Listing::query()->whereKey($listing->id)->update(['status' => ListingStatus::Inactive->value]);

    expect(fn () => app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(fields: ['price' => 111_000_000])))
        ->toThrow(InvalidListingStatusTransitionException::class);

    $fresh = $listing->fresh();
    expect($fresh->status)->toBe(ListingStatus::Inactive)
        ->and($fresh->price)->toBe($originalPrice);
});

it('adds, removes, and re-covers photos without orphan files', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner);

    foreach ($listing->photos as $photo) {
        Storage::disk('private')->put($photo->storage_path, 'dummy-bytes');
    }

    $oldCover = $listing->photos->firstWhere('is_cover', true);
    $removed = $listing->photos->where('is_cover', false)->first();

    $result = app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(
        removePhotoIds: [$removed->id],
        newPhotos: [updateListingFakeImage('new1.jpg'), updateListingFakeImage('new2.jpg')],
    ));

    $freshPhotos = $result->fresh()->photos;
    expect($freshPhotos)->toHaveCount(4)
        ->and($freshPhotos->pluck('id')->all())->not->toContain($removed->id)
        ->and($freshPhotos->where('is_cover', true))->toHaveCount(1)
        ->and($freshPhotos->firstWhere('is_cover', true)->id)->toBe($oldCover->id);

    Storage::disk('private')->assertExists($freshPhotos->last()->storage_path);
    Storage::disk('private')->assertMissing($removed->storage_path);

    $newCover = $freshPhotos->where('is_cover', false)->first();
    app(UpdateListing::class)->handle($owner, $listing->fresh(), new UpdateListingDTO(coverPhotoId: $newCover->id));

    $covers = $listing->fresh()->photos->where('is_cover', true);
    expect($covers)->toHaveCount(1)
        ->and($covers->first()->id)->toBe($newCover->id);
});

it('lets a newly uploaded photo become the cover', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner);

    app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(
        newPhotos: [updateListingFakeImage('cover-new.jpg')],
        coverNewPhotoIndex: 0,
    ));

    $freshPhotos = $listing->fresh()->photos;
    expect($freshPhotos)->toHaveCount(4);

    $covers = $freshPhotos->where('is_cover', true);
    expect($covers)->toHaveCount(1)
        ->and($covers->first()->sort_order)->toBe($freshPhotos->max('sort_order'));

    Storage::disk('private')->assertExists($covers->first()->storage_path);
});

it('falls back to the first retained photo when the cover is removed', function () {
    $owner = User::factory()->user()->create();
    $listing = Listing::factory()->active()->withPhotos(4)->create(['seller_id' => $owner->id]);

    $cover = $listing->photos->firstWhere('is_cover', true);

    app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(removePhotoIds: [$cover->id]));

    $freshPhotos = $listing->fresh()->photos;
    expect($freshPhotos)->toHaveCount(3)
        ->and($freshPhotos->where('is_cover', true))->toHaveCount(1);
});

it('rejects photo changes that break the 3 to 10 rule and cleans up new files', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner);
    $action = app(UpdateListing::class);

    $tooMany = array_map(fn (int $i): UploadedFile => updateListingFakeImage("extra{$i}.jpg"), range(1, 8));

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(newPhotos: $tooMany)))
        ->toThrow(PhotoUploadException::class);

    $removeAll = $listing->photos->pluck('id')->all();
    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(removePhotoIds: $removeAll)))
        ->toThrow(PhotoUploadException::class);

    expect($listing->fresh()->photos)->toHaveCount(3);
    expect(Storage::disk('private')->allFiles('listings/'.$listing->id))->toBe([]);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(newPhotos: [updateListingFakeImage('huge.jpg', 2100)])))
        ->toThrow(PhotoUploadException::class);

    $spoofed = UploadedFile::fake()->createWithContent('evil.jpg', '<?php echo "x"; ?>');
    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(newPhotos: [updateListingFakeImage('ok.jpg'), $spoofed])))
        ->toThrow(PhotoUploadException::class);

    expect(Storage::disk('private')->allFiles('listings/'.$listing->id))->toBe([]);
});

it('ignores photo ids that belong to another listing', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner);
    $stranger = Listing::factory()->active()->withPhotos(3)->create();
    $foreignPhotoId = $stranger->photos->first()->id;

    app(UpdateListing::class)->handle($owner, $listing, new UpdateListingDTO(removePhotoIds: [$foreignPhotoId]));

    expect($listing->fresh()->photos)->toHaveCount(3)
        ->and($stranger->fresh()->photos)->toHaveCount(3);
});

it('replaces inspection slots and validates them', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner);
    $action = app(UpdateListing::class);

    $action->handle($owner, $listing, new UpdateListingDTO(slots: [
        ['slot_start' => now()->addDays(4)->setHour(9)->setMinute(0)->toDateTimeString(), 'note' => 'Pagi'],
        ['slot_start' => now()->addDays(5)->setHour(15)->setMinute(0)->toDateTimeString(), 'note' => null],
    ]));

    expect($listing->fresh()->inspectionSlots)->toHaveCount(2);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(slots: [])))
        ->toThrow(ListingValidationException::class);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(slots: [
        ['slot_start' => now()->subDay()->toDateTimeString()],
    ])))->toThrow(ListingValidationException::class);
});

it('validates the map pin with the same Pekanbaru boundary', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner);
    $action = app(UpdateListing::class);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(fields: ['lat' => 3.0, 'lng' => 101.4])))
        ->toThrow(ListingValidationException::class);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(fields: ['lat' => 0.51])))
        ->toThrow(ListingValidationException::class);

    $action->handle($owner, $listing, new UpdateListingDTO(fields: [
        'lat' => 0.52,
        'lng' => 101.45,
        'address_full' => 'Jl. Baru No. 99, Tampan, Pekanbaru',
        'area_name' => 'Tampan',
    ]));

    $fresh = $listing->fresh();
    expect((float) $fresh->lat)->toBe(0.52)
        ->and((float) $fresh->lng)->toBe(101.45)
        ->and($fresh->status)->toBe(ListingStatus::Active);
});

it('rejects invalid scalar values', function () {
    $owner = User::factory()->user()->create();
    $listing = activeListingForEdit($owner);
    $action = app(UpdateListing::class);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(fields: ['vehicle_type' => 'plane'])))
        ->toThrow(ListingValidationException::class);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(fields: ['year' => 1980])))
        ->toThrow(ListingValidationException::class);

    expect(fn () => $action->handle($owner, $listing, new UpdateListingDTO(fields: ['price' => 0])))
        ->toThrow(ListingValidationException::class);

    expect($listing->fresh()->status)->toBe(ListingStatus::Active);
});
