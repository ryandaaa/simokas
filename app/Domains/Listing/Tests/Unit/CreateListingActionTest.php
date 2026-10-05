<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\CreateListing;
use App\Domains\Listing\DTOs\CreateListingDTO;
use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Events\ListingCreated;
use App\Domains\Listing\Exceptions\ListingValidationException;
use App\Domains\Listing\Exceptions\PhotoUploadException;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
    Event::fake([ListingCreated::class]);
});

function createDummyImage(string $name = 'photo.jpg', int $sizeKilobytes = 50): UploadedFile
{
    return UploadedFile::fake()->image($name)->size($sizeKilobytes);
}

it('creates a listing with status pending_review, stores photos on private disk, and dispatches event', function () {
    $seller = User::factory()->user()->create();
    $action = app(CreateListing::class);

    $photos = [
        createDummyImage('car1.jpg'),
        createDummyImage('car2.png'),
        createDummyImage('car3.webp'),
    ];

    $slots = [
        [
            'slot_start' => now()->addDays(2)->toIso8601String(),
            'note' => 'Bisa pagi jam 10',
        ],
    ];

    $dto = new CreateListingDTO(
        vehicle_type: VehicleType::Car,
        title: 'Toyota Avanza 1.3 G Manual 2018',
        brand: 'Toyota',
        model: 'Avanza',
        year: 2018,
        price: 135_000_000,
        negotiable: true,
        odometer_km: 62000,
        transmission: Transmission::Manual,
        fuel: FuelType::Petrol,
        color: 'Hitam Metalik',
        inspection_place: 'Jl. Sudirman No. 45, Pekanbaru',
        photos: $photos,
        slots: $slots,
        chassis_number: 'MHKA1234567890123',
        description: 'Tangan pertama dari baru.',
        area_name: 'Sukajadi',
        lat: 0.5071,
        lng: 101.4478,
        address_full: 'Jl. HR Soebrantas No. 12, Sukajadi, Pekanbaru',
        cover_photo_index: 0,
    );

    $listing = $action->handle($seller, $dto);

    expect($listing)->toBeInstanceOf(Listing::class)
        ->and($listing->seller_id)->toBe($seller->id)
        ->and($listing->status)->toBe(ListingStatus::PendingReview)
        ->and($listing->photos)->toHaveCount(3)
        ->and($listing->inspectionSlots)->toHaveCount(1);

    // Verify exactly one cover photo
    $coverPhotos = $listing->photos->where('is_cover', true);
    expect($coverPhotos)->toHaveCount(1)
        ->and($coverPhotos->first()->sort_order)->toBe(0);

    // Verify stored files exist on private disk
    foreach ($listing->photos as $photo) {
        Storage::disk('private')->assertExists($photo->storage_path);
    }

    Event::assertDispatched(ListingCreated::class, fn (ListingCreated $event) => $event->listing->id === $listing->id);
});

it('rejects listing creation when photos count is less than 3', function () {
    $seller = User::factory()->user()->create();
    $action = app(CreateListing::class);

    $photos = [
        createDummyImage('p1.jpg'),
        createDummyImage('p2.jpg'),
    ];

    $dto = new CreateListingDTO(
        vehicle_type: VehicleType::Car,
        title: 'Toyota Avanza 1.3 G Manual 2018',
        brand: 'Toyota',
        model: 'Avanza',
        year: 2018,
        price: 135_000_000,
        negotiable: true,
        odometer_km: 62000,
        transmission: Transmission::Manual,
        fuel: FuelType::Petrol,
        color: 'Hitam',
        inspection_place: 'Rumah',
        photos: $photos,
        slots: [['slot_start' => now()->addDay()->toIso8601String()]],
    );

    $action->handle($seller, $dto);
})->throws(PhotoUploadException::class);

it('rejects listing creation when photos count is more than 10', function () {
    $seller = User::factory()->user()->create();
    $action = app(CreateListing::class);

    $photos = [];
    for ($i = 0; $i < 11; $i++) {
        $photos[] = createDummyImage("photo_{$i}.jpg");
    }

    $dto = new CreateListingDTO(
        vehicle_type: VehicleType::Car,
        title: 'Toyota Avanza 1.3 G Manual 2018',
        brand: 'Toyota',
        model: 'Avanza',
        year: 2018,
        price: 135_000_000,
        negotiable: true,
        odometer_km: 62000,
        transmission: Transmission::Manual,
        fuel: FuelType::Petrol,
        color: 'Hitam',
        inspection_place: 'Rumah',
        photos: $photos,
        slots: [['slot_start' => now()->addDay()->toIso8601String()]],
    );

    $action->handle($seller, $dto);
})->throws(PhotoUploadException::class);

it('rejects photo exceeding 2 MB size limit', function () {
    $seller = User::factory()->user()->create();
    $action = app(CreateListing::class);

    $photos = [
        createDummyImage('p1.jpg', 100),
        createDummyImage('p2.jpg', 100),
        createDummyImage('huge.jpg', 2100), // > 2MB
    ];

    $dto = new CreateListingDTO(
        vehicle_type: VehicleType::Car,
        title: 'Toyota Avanza 1.3 G Manual 2018',
        brand: 'Toyota',
        model: 'Avanza',
        year: 2018,
        price: 135_000_000,
        negotiable: true,
        odometer_km: 62000,
        transmission: Transmission::Manual,
        fuel: FuelType::Petrol,
        color: 'Hitam',
        inspection_place: 'Rumah',
        photos: $photos,
        slots: [['slot_start' => now()->addDay()->toIso8601String()]],
    );

    $action->handle($seller, $dto);
})->throws(PhotoUploadException::class);

it('rejects spoofed file with .jpg extension containing non-image content', function () {
    $seller = User::factory()->user()->create();
    $action = app(CreateListing::class);

    // Create a text file disguised as a .jpg
    $spoofedFile = UploadedFile::fake()->createWithContent('malicious.jpg', '<?php echo "evil script"; ?>');

    $photos = [
        createDummyImage('p1.jpg'),
        createDummyImage('p2.jpg'),
        $spoofedFile,
    ];

    $dto = new CreateListingDTO(
        vehicle_type: VehicleType::Car,
        title: 'Toyota Avanza 1.3 G Manual 2018',
        brand: 'Toyota',
        model: 'Avanza',
        year: 2018,
        price: 135_000_000,
        negotiable: true,
        odometer_km: 62000,
        transmission: Transmission::Manual,
        fuel: FuelType::Petrol,
        color: 'Hitam',
        inspection_place: 'Rumah',
        photos: $photos,
        slots: [['slot_start' => now()->addDay()->toIso8601String()]],
    );

    $action->handle($seller, $dto);
})->throws(PhotoUploadException::class);

it('rejects listing creation with empty inspection slots', function () {
    $seller = User::factory()->user()->create();
    $action = app(CreateListing::class);

    $photos = [
        createDummyImage('p1.jpg'),
        createDummyImage('p2.jpg'),
        createDummyImage('p3.jpg'),
    ];

    $dto = new CreateListingDTO(
        vehicle_type: VehicleType::Car,
        title: 'Toyota Avanza 1.3 G Manual 2018',
        brand: 'Toyota',
        model: 'Avanza',
        year: 2018,
        price: 135_000_000,
        negotiable: true,
        odometer_km: 62000,
        transmission: Transmission::Manual,
        fuel: FuelType::Petrol,
        color: 'Hitam',
        inspection_place: 'Rumah',
        photos: $photos,
        slots: [],
    );

    $action->handle($seller, $dto);
})->throws(ListingValidationException::class);

it('rejects listing creation with inspection slot in the past', function () {
    $seller = User::factory()->user()->create();
    $action = app(CreateListing::class);

    $photos = [
        createDummyImage('p1.jpg'),
        createDummyImage('p2.jpg'),
        createDummyImage('p3.jpg'),
    ];

    $dto = new CreateListingDTO(
        vehicle_type: VehicleType::Car,
        title: 'Toyota Avanza 1.3 G Manual 2018',
        brand: 'Toyota',
        model: 'Avanza',
        year: 2018,
        price: 135_000_000,
        negotiable: true,
        odometer_km: 62000,
        transmission: Transmission::Manual,
        fuel: FuelType::Petrol,
        color: 'Hitam',
        inspection_place: 'Rumah',
        photos: $photos,
        slots: [
            ['slot_start' => now()->subDay()->toIso8601String()],
        ],
    );

    $action->handle($seller, $dto);
})->throws(ListingValidationException::class);

it('cleans up uploaded files on private disk if database transaction fails', function () {
    $seller = User::factory()->user()->create();
    $action = app(CreateListing::class);

    $photos = [
        createDummyImage('p1.jpg'),
        createDummyImage('p2.jpg'),
        createDummyImage('p3.jpg'),
    ];

    // Invalid slot start that passes initial check but fails carbon parsing in transaction or we simulate failure
    $dto = new CreateListingDTO(
        vehicle_type: VehicleType::Car,
        title: 'Toyota Avanza 1.3 G Manual 2018',
        brand: 'Toyota',
        model: 'Avanza',
        year: 2018,
        price: 135_000_000,
        negotiable: true,
        odometer_km: 62000,
        transmission: Transmission::Manual,
        fuel: FuelType::Petrol,
        color: 'Hitam',
        inspection_place: 'Rumah',
        photos: $photos,
        slots: [
            ['slot_start' => now()->addDay()->toIso8601String()],
            ['slot_start' => 'invalid-datetime-string-causes-crash'],
        ],
    );

    try {
        $action->handle($seller, $dto);
        expect(false)->toBeTrue('Expected exception was not thrown.');
    } catch (Throwable $e) {
        // Assert private disk has 0 files left (no orphaned files)
        expect(Storage::disk('private')->allFiles())->toBeEmpty();
    }
});

it('rejects inspector and admin from creating listings', function () {
    $action = app(CreateListing::class);
    $inspector = User::factory()->inspector()->create();
    $admin = User::factory()->admin()->create();

    $photos = [
        createDummyImage('p1.jpg'),
        createDummyImage('p2.jpg'),
        createDummyImage('p3.jpg'),
    ];

    $dto = new CreateListingDTO(
        vehicle_type: VehicleType::Car,
        title: 'Toyota Avanza 1.3 G Manual 2018',
        brand: 'Toyota',
        model: 'Avanza',
        year: 2018,
        price: 135_000_000,
        negotiable: true,
        odometer_km: 62000,
        transmission: Transmission::Manual,
        fuel: FuelType::Petrol,
        color: 'Hitam',
        inspection_place: 'Rumah',
        photos: $photos,
        slots: [['slot_start' => now()->addDay()->toIso8601String()]],
    );

    expect(fn () => $action->handle($inspector, $dto))->toThrow(ListingValidationException::class)
        ->and(fn () => $action->handle($admin, $dto))->toThrow(ListingValidationException::class);
});
