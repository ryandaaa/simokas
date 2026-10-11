<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Listing\Actions\CreateListing as CreateListingAction;
use App\Domains\Listing\DTOs\CreateListingDTO;
use App\Domains\Listing\Enums\FuelType;
use App\Domains\Listing\Enums\ListingStatus;
use App\Domains\Listing\Enums\Transmission;
use App\Domains\Listing\Enums\VehicleType;
use App\Domains\Listing\Exceptions\ListingValidationException;
use App\Domains\Listing\Http\Resources\ListingPublicResource;
use App\Domains\Listing\Livewire\CreateListing;
use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\ValueObjects\PekanbaruBoundary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Helpers\ListingPrivacyHelper;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('private');
});

function locationFakeImage(string $name = 'car.jpg'): UploadedFile
{
    return UploadedFile::fake()->image($name)->size(50);
}

function validLocationPayload(): array
{
    return [
        'vehicle_type' => 'car',
        'title' => 'Toyota Avanza 1.3 G Manual 2018',
        'brand' => 'Toyota',
        'model' => 'Avanza',
        'year' => 2018,
        'price' => 135_000_000,
        'negotiable' => true,
        'odometer_km' => 62000,
        'transmission' => 'manual',
        'fuel' => 'petrol',
        'color' => 'Hitam Metalik',
        'inspection_place' => 'Jl. HR Soebrantas No. 10, Tampan, Pekanbaru',
        'lat' => 0.5071,
        'lng' => 101.4478,
        'address_full' => 'Jl. HR Soebrantas No. 10, Tampan, Pekanbaru',
        'area_name' => 'Tampan',
        'photos' => [locationFakeImage('1.jpg'), locationFakeImage('2.jpg'), locationFakeImage('3.jpg')],
        'cover_index' => 0,
        'inspection_slots' => [['slot_start' => now()->addDays(2)->format('Y-m-d\TH:i'), 'note' => '']],
    ];
}

it('LST-T03 rejects a listing pin outside Pekanbaru on the server (dataset)', function (float $lat, float $lng) {
    $seller = User::factory()->user()->create();
    actingAs($seller);

    $payload = validLocationPayload();
    $payload['lat'] = $lat;
    $payload['lng'] = $lng;

    $component = Livewire::test(CreateListing::class);

    foreach ($payload as $field => $value) {
        $component->set($field, $value);
    }

    $component->call('submit')->assertHasErrors(['lat']);

    expect(Listing::query()->count())->toBe(0);
})->with([
    'Jakarta' => [-6.2000, 106.8000],
    'just north of boundary' => [PekanbaruBoundary::MAX_LAT + 0.01, 101.4500],
    'just south of boundary' => [PekanbaruBoundary::MIN_LAT - 0.01, 101.4500],
    'just west of boundary' => [0.5100, PekanbaruBoundary::MIN_LNG - 0.01],
    'just east of boundary' => [0.5100, PekanbaruBoundary::MAX_LNG + 0.01],
]);

it('LST-T03 rejects an outside-Pekanbaru point in the CreateListing action even when Livewire validation is bypassed', function () {
    $seller = User::factory()->user()->create();

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
        inspection_place: 'Jl. HR Soebrantas No. 10',
        photos: [locationFakeImage('1.jpg'), locationFakeImage('2.jpg'), locationFakeImage('3.jpg')],
        slots: [['slot_start' => now()->addDays(2)->toIso8601String()]],
        area_name: 'Tampan',
        lat: -6.2,
        lng: 106.8,
        address_full: 'Jl. Sudirman No. 1, Jakarta',
    );

    expect(fn () => app(CreateListingAction::class)->handle($seller, $dto))
        ->toThrow(ListingValidationException::class, 'Pekanbaru');

    expect(Listing::query()->count())->toBe(0);
});

it('LST-T03 accepts a listing pin on the Pekanbaru boundary edge', function () {
    $seller = User::factory()->user()->create();
    actingAs($seller);

    $payload = validLocationPayload();
    $payload['lat'] = PekanbaruBoundary::MIN_LAT;
    $payload['lng'] = PekanbaruBoundary::MIN_LNG;

    $component = Livewire::test(CreateListing::class);

    foreach ($payload as $field => $value) {
        $component->set($field, $value);
    }

    $component->call('submit')->assertHasNoErrors()->assertRedirect(route('listings.mine'));

    expect(Listing::query()->count())->toBe(1);
});

it('validates coordinate types and ranges on the server (dataset)', function (string $field, mixed $value) {
    $seller = User::factory()->user()->create();
    actingAs($seller);

    $payload = validLocationPayload();
    $payload[$field] = $value;

    $component = Livewire::test(CreateListing::class);

    foreach ($payload as $attr => $attrValue) {
        $component->set($attr, $attrValue);
    }

    $component->call('submit')->assertHasErrors([$field]);

    expect(Listing::query()->count())->toBe(0);
})->with([
    'lat as text' => ['lat', 'tengah kota'],
    'lng as text' => ['lng', 'timur'],
    'lat as NaN string' => ['lat', 'NaN'],
    'lat empty' => ['lat', ''],
    'lng empty' => ['lng', ''],
    'lat above 90' => ['lat', 91],
    'lat below -90' => ['lat', -91],
    'lng above 180' => ['lng', 181],
    'lng below -180' => ['lng', -181],
    'address_full empty' => ['address_full', ''],
    'address_full too short' => ['address_full', 'Pekanbaru'],
    'area_name empty' => ['area_name', ''],
]);

it('forbids another owner from planting coordinates on someone else listing', function () {
    $owner = User::factory()->user()->create();
    $intruder = User::factory()->user()->create();

    $listing = Listing::factory()->create([
        'seller_id' => $owner->id,
        'lat' => 0.5071,
        'lng' => 101.4478,
    ]);

    actingAs($intruder);
    get(route('listings.edit', $listing))->assertForbidden();

    expect($listing->fresh()->lat)->toBe('0.5071000')
        ->and($listing->fresh()->lng)->toBe('101.4478000');
});

it('LST-T11 never leaks exact location values in public JSON or public HTML', function () {
    $seller = User::factory()->user()->create();

    $listing = Listing::factory()->active()->create([
        'seller_id' => $seller->id,
        'lat' => 0.5071234,
        'lng' => 101.4478123,
        'address_full' => 'Jl. Melati Gang Mawar Nomor 8 RT 02 Pekanbaru',
        'chassis_number' => 'MHKA1234567890123',
        'inspection_place' => 'Garasi rumah penjual di gang mawar',
        'area_name' => 'Sukajadi',
    ]);

    $request = Request::create('/listings/'.$listing->id);
    $publicArray = (new ListingPublicResource($listing))->toArray($request);

    ListingPrivacyHelper::assertNoPrivateListingData($publicArray, [
        'Jl. Melati Gang Mawar Nomor 8 RT 02 Pekanbaru',
        'MHKA1234567890123',
        'Garasi rumah penjual di gang mawar',
        '0.5071234',
        '101.4478123',
    ]);

    expect($publicArray['area_name'])->toBe('Sukajadi');

    // Simulated public HTML (as CAT-2 will render it): only area_name is shown.
    $html = view('listing::partials.public-listing-snippet', ['listing' => $listing])->render();

    ListingPrivacyHelper::assertNoPrivateListingData($html, [
        'Jl. Melati Gang Mawar Nomor 8 RT 02 Pekanbaru',
        'MHKA1234567890123',
        'Garasi rumah penjual di gang mawar',
    ]);

    expect($html)->toContain('Sukajadi');
});

it('LST-T19 preloads the map entry before the location step and renders the picker with OSM attribution', function () {
    $seller = User::factory()->user()->create();
    actingAs($seller);

    // The map module must be ready before Livewire morphs the location step into the page.
    get(route('listings.create'))->assertOk()->assertSee('assets/listing-map', false);

    $payload = validLocationPayload();

    $component = Livewire::test(CreateListing::class);

    foreach (['vehicle_type', 'title', 'brand', 'model', 'year', 'price', 'odometer_km', 'transmission', 'fuel', 'color'] as $field) {
        $component->set($field, $payload[$field]);
    }

    $component->call('nextStep')->assertSet('step', 2)
        ->set('photos', $payload['photos'])
        ->set('cover_index', 0)
        ->call('nextStep')->assertSet('step', 3)
        ->assertSee('data-listing-map', false)
        ->assertSee('openstreetmap.org/copyright', false)
        ->assertSee('data-locate-me', false)
        ->assertSee(__('listing.fields.use_my_location'))
        ->assertSee('name="lat"', false)
        ->assertSee('name="lng"', false)
        ->assertSee('name="address_full"', false);

    $html = $component->html();

    expect($html)->not->toContain('style="');

    // The global layout must never pull the map bundle; the component owns this page-specific asset.
    $layout = (string) file_get_contents(resource_path('views/components/layouts/app.blade.php'));

    expect($layout)->not->toContain('leaflet')
        ->and($layout)->not->toContain('listing-map');
});

it('LST-T23 keeps Vite assets outside the Livewire component root in the create page response', function () {
    $seller = User::factory()->user()->create();
    actingAs($seller);

    $response = get(route('listings.create'))->assertOk();
    $html = (string) $response->getContent();

    preg_match('/<([a-z][a-z0-9:-]*)\\b[^>]*\\bwire:id="[^"]+"[^>]*>/i', $html, $matches);

    expect($matches[1] ?? null)->toBe('div')
        ->and($html)->toContain('assets/listing-map');
});

it('LST-T03 keeps listings out of the database when location validation fails', function () {
    $seller = User::factory()->user()->create();
    actingAs($seller);

    $payload = validLocationPayload();
    $payload['lat'] = -6.2;
    $payload['lng'] = 106.8;

    $component = Livewire::test(CreateListing::class);

    foreach ($payload as $field => $value) {
        $component->set($field, $value);
    }

    $component->call('submit')->assertHasErrors(['lat']);

    expect(Listing::query()->where('status', ListingStatus::PendingReview)->count())->toBe(0);
});

it('LST-T21 initializes the map without passing browser events as DOM roots', function () {
    $source = (string) file_get_contents(resource_path('js/listing-map.js'));

    expect(str_contains($source, "document.addEventListener('DOMContentLoaded', () => initAll());"))->toBeTrue()
        ->and(str_contains($source, "document.addEventListener('livewire:navigated', () => initAll());"))->toBeTrue()
        ->and(str_contains($source, "document.addEventListener('DOMContentLoaded', initAll);"))->toBeFalse()
        ->and(str_contains($source, "document.addEventListener('livewire:navigated', initAll);"))->toBeFalse();
});

it('LST-T22 keeps Vite asset tags inside the Livewire component root', function () {
    $seller = User::factory()->user()->create();
    actingAs($seller);

    $html = Livewire::test(CreateListing::class)->html();
    preg_match('/^\s*<([a-zA-Z0-9-]+)\b[^>]*wire:id=/', $html, $matches);

    expect($matches[1] ?? null)->toBe('div');
});
