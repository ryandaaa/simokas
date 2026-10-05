<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Livewire\CreateListing;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
});

function fakeImage(string $name = 'car.jpg', int $sizeKb = 50): UploadedFile
{
    return UploadedFile::fake()->image($name)->size($sizeKb);
}

it('LST-T01 seller creates valid listing, status pending_review, does not appear in public active query', function () {
    $seller = User::factory()->user()->create();

    actingAs($seller);

    $photos = [
        fakeImage('car1.jpg'),
        fakeImage('car2.jpg'),
        fakeImage('car3.jpg'),
    ];

    $slots = [
        [
            'slot_start' => now()->addDays(3)->setHour(14)->setMinute(0)->format('Y-m-d\TH:i'),
            'note' => 'Di garasi rumah',
        ],
    ];

    Livewire::test(CreateListing::class)
        ->set('vehicle_type', 'car')
        ->set('title', 'Toyota Avanza 1.3 G Manual 2018')
        ->set('brand', 'Toyota')
        ->set('model', 'Avanza')
        ->set('year', 2018)
        ->set('price', 135_000_000)
        ->set('negotiable', true)
        ->set('odometer_km', 62000)
        ->set('transmission', 'manual')
        ->set('fuel', 'petrol')
        ->set('color', 'Hitam Metalik')
        ->set('chassis_number', 'MHKA1234567890123')
        ->set('description', 'Kondisi mulus terawat, siap pakai.')
        ->set('area_name', 'Tampan')
        ->set('inspection_place', 'Jl. HR Soebrantas No. 10, Tampan, Pekanbaru')
        ->set('photos', $photos)
        ->set('cover_index', 0)
        ->set('inspection_slots', $slots)
        ->call('submit')
        ->assertRedirect(route('listings.mine'));

    // Verify database record
    /** @var Listing $listing */
    $listing = Listing::query()->first();

    expect($listing)->not->toBeNull()
        ->and($listing->seller_id)->toBe($seller->id)
        ->and($listing->title)->toBe('Toyota Avanza 1.3 G Manual 2018')
        ->and($listing->status)->toBe(ListingStatus::PendingReview)
        ->and($listing->photos)->toHaveCount(3)
        ->and($listing->inspectionSlots)->toHaveCount(1);

    // Verify it DOES NOT appear in active public listings query
    $publicActiveListings = Listing::query()->active()->get();
    expect($publicActiveListings)->toBeEmpty();

    // Verify raw database chassis_number is stored encrypted (not plain text)
    $rawListing = DB::table('listings')->where('id', $listing->id)->first();
    expect($rawListing->chassis_number)->not->toBe('MHKA1234567890123')
        ->and($rawListing->chassis_number)->toContain('eyJ'); // Laravel standard encryption envelope prefix

    // Verify decrypted chassis_number via Eloquent model
    expect($listing->fresh()->chassis_number)->toBe('MHKA1234567890123');
});

it('LST-T02 validates listing fields according to PRD 13.3 (dataset)', function (string $field, mixed $invalidValue, string $expectedError) {
    $seller = User::factory()->user()->create();
    actingAs($seller);

    $component = Livewire::test(CreateListing::class)
        ->set('vehicle_type', 'car')
        ->set('title', 'Toyota Avanza 1.3 G Manual 2018')
        ->set('brand', 'Toyota')
        ->set('model', 'Avanza')
        ->set('year', 2018)
        ->set('price', 135_000_000)
        ->set('negotiable', true)
        ->set('odometer_km', 62000)
        ->set('transmission', 'manual')
        ->set('fuel', 'petrol')
        ->set('color', 'Hitam Metalik')
        ->set('inspection_place', 'Jl. HR Soebrantas No. 10')
        ->set('inspection_slots', [['slot_start' => now()->addDays(2)->format('Y-m-d\TH:i'), 'note' => '']]);

    if ($field !== 'photos') {
        $component->set('photos', [fakeImage('1.jpg'), fakeImage('2.jpg'), fakeImage('3.jpg')]);
    }

    $actualValue = is_callable($invalidValue) ? $invalidValue() : $invalidValue;
    $component->set($field, $actualValue);

    if (str_starts_with($field, 'inspection_slots') || $field === 'inspection_place') {
        $component->call('submit')->assertHasErrors([$expectedError]);
    } elseif ($field === 'photos') {
        $component->call('validateStep2')->assertHasErrors([$expectedError]);
    } else {
        $component->call('validateStep1')->assertHasErrors([$expectedError]);
    }

})->with([
    'year below 1990' => ['year', 1989, 'year'],
    'year above current year' => ['year', (int) date('Y') + 1, 'year'],
    'price zero' => ['price', 0, 'price'],
    'price negative' => ['price', -50000, 'price'],
    'odometer negative' => ['odometer_km', -1, 'odometer_km'],
    'odometer above 999999' => ['odometer_km', 1000000, 'odometer_km'],
    'title too short' => ['title', 'Avanza', 'title'],
    'title too long' => ['title', str_repeat('a', 101), 'title'],
    'photos less than 3' => ['photos', fn () => [fakeImage('1.jpg'), fakeImage('2.jpg')], 'photos'],
    'photos more than 10' => ['photos', fn () => array_fill(0, 11, fakeImage('p.jpg')), 'photos'],
    'slot in past' => ['inspection_slots', [['slot_start' => now()->subDay()->format('Y-m-d\TH:i'), 'note' => '']], 'inspection_slots.0.slot_start'],
    'inspection place empty' => ['inspection_place', '', 'inspection_place'],
]);

it('forbids guests and inspectors from accessing create listing form', function () {
    // Guest
    get(route('listings.create'))
        ->assertRedirect(route('login'));

    // Inspector
    $inspector = User::factory()->inspector()->create();
    actingAs($inspector);
    get(route('listings.create'))
        ->assertForbidden();

    // Blocked user
    $blocked = User::factory()->user()->blocked()->create();
    actingAs($blocked);
    get(route('listings.create'))
        ->assertRedirect('/blocked');
});

it('ensures owner is always authenticated user and cannot be spoofed', function () {
    $seller = User::factory()->user()->create();
    $otherUser = User::factory()->user()->create();
    actingAs($seller);

    Livewire::test(CreateListing::class)
        ->set('title', 'Toyota Avanza 1.3 G Manual 2018')
        ->set('brand', 'Toyota')
        ->set('model', 'Avanza')
        ->set('year', 2018)
        ->set('price', 135_000_000)
        ->set('odometer_km', 62000)
        ->set('color', 'Hitam Metalik')
        ->set('inspection_place', 'Jl. HR Soebrantas No. 10')
        ->set('photos', [fakeImage('1.jpg'), fakeImage('2.jpg'), fakeImage('3.jpg')])
        ->set('inspection_slots', [['slot_start' => now()->addDays(2)->format('Y-m-d\TH:i'), 'note' => '']])
        ->call('submit');

    $listing = Listing::query()->first();
    expect($listing->seller_id)->toBe($seller->id)
        ->and($listing->seller_id)->not->toBe($otherUser->id);
});

it('tests step navigation, vehicle type change, slot and photo manipulation', function () {
    $seller = User::factory()->user()->create();
    actingAs($seller);

    $component = Livewire::test(CreateListing::class)
        ->assertSet('step', 1)
        ->set('vehicle_type', 'car')
        ->set('brand', 'Toyota')
        ->set('vehicle_type', 'motorcycle')
        ->assertSet('brand', '')
        ->set('vehicle_type', 'car')
        ->set('title', 'Toyota Avanza 1.3 G Manual 2018')
        ->set('brand', 'Toyota')
        ->set('model', 'Avanza')
        ->set('year', 2018)
        ->set('price', 135_000_000)
        ->set('odometer_km', 62000)
        ->set('color', 'Hitam Metalik')
        ->call('nextStep')
        ->assertSet('step', 2)
        ->call('previousStep')
        ->assertSet('step', 1)
        ->call('nextStep')
        ->assertSet('step', 2)
        ->set('photos', [fakeImage('1.jpg'), fakeImage('2.jpg'), fakeImage('3.jpg'), fakeImage('4.jpg')])
        ->assertCount('photos', 4)
        ->call('setCover', 2)
        ->assertSet('cover_index', 2)
        ->call('removePhoto', 2)
        ->assertCount('photos', 3)
        ->call('nextStep')
        ->assertSet('step', 3)
        ->assertCount('inspection_slots', 1)
        ->call('addSlot')
        ->assertCount('inspection_slots', 2)
        ->call('removeSlot', 1)
        ->assertCount('inspection_slots', 1);
});
