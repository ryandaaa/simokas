<?php

declare(strict_types=1);

use App\Domains\Listing\Models\Listing;
use App\Domains\Listing\Models\ListingPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\ListingPrivacyHelper;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('CAT-T01 shows only active, in_inspection, and in_transaction listings to guests', function () {
    foreach (['pending_review', 'active', 'rejected', 'in_inspection', 'in_transaction', 'sold', 'inactive'] as $status) {
        Listing::factory()->create([
            'status' => $status,
            'title' => "Guest list vehicle {$status} 999",
        ]);
    }

    $response = get(route('catalog.index'))->assertOk();

    foreach (['active', 'in_inspection', 'in_transaction'] as $status) {
        $response->assertSee("Guest list vehicle {$status} 999");
    }

    foreach (['pending_review', 'rejected', 'sold', 'inactive'] as $status) {
        $response->assertDontSee("Guest list vehicle {$status} 999");
    }
});

it('CAT-T01 hides sold listings without any action button', function () {
    Listing::factory()->sold()->create([
        'title' => 'CAT sold hidden vehicle 999',
    ]);

    get(route('catalog.index'))->assertOk()->assertDontSee('CAT sold hidden vehicle 999');
});

it('CAT-T02 filters the public list by each filter and combined', function () {
    Listing::factory()->motorcycle()->active()->create([
        'title' => 'CAT-T02 Honda Vario target 999',
        'brand' => 'Honda',
        'model' => 'Vario 150',
        'price' => 20_000_000,
        'year' => 2021,
        'area_name' => 'Tampan',
        'is_inspected' => true,
        'last_inspected_at' => now(),
    ]);
    Listing::factory()->car()->active()->create([
        'title' => 'CAT-T02 Toyota Avanza other 999',
        'brand' => 'Toyota',
        'model' => 'Avanza',
        'price' => 150_000_000,
        'year' => 2019,
        'area_name' => 'Sukajadi',
    ]);

    $cases = [
        'keyword' => ['q' => 'vario'],
        'type' => ['type' => 'motorcycle'],
        'brand' => ['brand' => 'honda'],
        'price range' => ['price_min' => '10000000', 'price_max' => '30000000'],
        'year range' => ['year_min' => '2020', 'year_max' => '2022'],
        'area' => ['area' => 'tamp'],
        'inspected' => ['inspected' => 'inspected'],
        'combined' => [
            'q' => 'vario',
            'type' => 'motorcycle',
            'brand' => 'honda',
            'price_min' => '10000000',
            'price_max' => '30000000',
            'year_min' => '2020',
            'year_max' => '2022',
            'area' => 'tampan',
            'inspected' => 'inspected',
        ],
    ];

    foreach ($cases as $label => $params) {
        $content = get(route('catalog.index', $params))->assertOk()->getContent();

        expect($content)->toContain('CAT-T02 Honda Vario target 999');
        expect($content)->not->toContain('CAT-T02 Toyota Avanza other 999');
    }
});

it('CAT-T03 orders the public list per sort option', function () {
    Listing::factory()->active()->create([
        'title' => 'CAT-T03-A cheap old high km 999',
        'price' => 50_000_000,
        'odometer_km' => 100_000,
        'created_at' => now()->subDays(3),
    ]);
    Listing::factory()->active()->create([
        'title' => 'CAT-T03-B pricey new low km 999',
        'price' => 200_000_000,
        'odometer_km' => 10_000,
        'created_at' => now()->subDay(),
    ]);
    Listing::factory()->active()->create([
        'title' => 'CAT-T03-C mid newest mid km 999',
        'price' => 120_000_000,
        'odometer_km' => 50_000,
        'created_at' => now(),
    ]);

    $positions = function (string $sort): array {
        $content = get(route('catalog.index', ['sort' => $sort]))->assertOk()->getContent();

        return [
            strpos($content, 'CAT-T03-A cheap old high km 999'),
            strpos($content, 'CAT-T03-B pricey new low km 999'),
            strpos($content, 'CAT-T03-C mid newest mid km 999'),
        ];
    };

    [$a, $b, $c] = $positions('newest');
    expect($c < $b && $b < $a)->toBeTrue('newest order is wrong');

    [$a, $b, $c] = $positions('cheapest');
    expect($a < $c && $c < $b)->toBeTrue('cheapest order is wrong');

    [$a, $b, $c] = $positions('priciest');
    expect($b < $c && $c < $a)->toBeTrue('priciest order is wrong');

    [$a, $b, $c] = $positions('lowest_km');
    expect($b < $c && $c < $a)->toBeTrue('lowest mileage order is wrong');
});

it('CAT-T04 paginates twelve cards per page with a correct total', function () {
    Listing::factory()->active()->count(13)->create();

    $first = get(route('catalog.index'))->assertOk();
    $second = get(route('catalog.index', ['page' => 2]))->assertOk();

    expect(substr_count($first->getContent(), '<article'))->toBe(12)
        ->and(substr_count($second->getContent(), '<article'))->toBe(1)
        ->and($first->getContent())->toContain('13');
});

it('CAT-T03 redirects an out-of-range page to the last page', function () {
    Listing::factory()->active()->count(13)->create();

    get(route('catalog.index', ['page' => 99]))
        ->assertRedirect(route('catalog.index', ['page' => 2]));
});

it('CAT-T05 never errors on invalid catalog parameters', function (mixed $params) {
    Listing::factory()->active()->create([
        'title' => 'CAT-T05 robust vehicle 999',
    ]);

    get(route('catalog.index', $params))->assertOk();
})->with([
    'page as string' => [['page' => 'abc']],
    'page as array' => [['page' => ['2']]],
    'page negative' => [['page' => '-5']],
    'negative price' => [['price_min' => '-1']],
    'inverted price range' => [['price_min' => '200000000', 'price_max' => '100000000']],
    'year out of range' => [['year_min' => '1800', 'year_max' => '9999']],
    'inverted year range' => [['year_min' => '2022', 'year_max' => '2018']],
    'unknown sort' => [['sort' => 'hack']],
    'unknown type' => [['type' => 'spaceship']],
    'array instead of string' => [['q' => ['avanza'], 'brand' => ['toyota']]],
    'overlong keyword' => [['q' => str_repeat('a', 500)]],
    'special characters' => [['q' => '<script>alert(1)</script>']],
    'like wildcards' => [['q' => '100%_avenza']],
]);

it('CAT-T05 escapes wildcard keywords without breaking the query', function () {
    Listing::factory()->active()->create([
        'title' => 'CAT-T05 promo diskon 50% khusus 999',
    ]);
    Listing::factory()->active()->create([
        'title' => 'CAT-T05 Toyota Avanza standar 999',
    ]);

    $percent = get(route('catalog.index', ['q' => '%']))->assertOk()->getContent();
    expect($percent)->toContain('CAT-T05 promo diskon 50% khusus 999')
        ->and($percent)->not->toContain('CAT-T05 Toyota Avanza standar 999');

    get(route('catalog.index', ['q' => '_']))->assertOk()->assertDontSee('CAT-T05 Toyota Avanza standar 999');
});

it('CAT-T10 shows an empty state with a clear-filters action when nothing matches', function () {
    Listing::factory()->active()->create([
        'title' => 'CAT-T10 existing vehicle 999',
    ]);

    $response = get(route('catalog.index', ['q' => 'kata-kunci-yang-tidak-ada-999']))->assertOk();

    $response->assertSee(__('catalog.empty_title'))
        ->assertSee(__('catalog.reset'))
        ->assertSee(route('catalog.index'), false)
        ->assertDontSee('CAT-T10 existing vehicle 999');
});

it('CAT-T06 keeps private listing data out of the public catalog page', function () {
    Listing::factory()->active()->create([
        'title' => 'CAT-T06 privacy vehicle 999',
        'lat' => 0.5071000,
        'lng' => 101.4478000,
        'address_full' => 'Jl. Rahasia Catalog No. 77, Pekanbaru',
        'chassis_number' => 'MHKA7777777777777',
        'inspection_place' => 'Jl. Rahasia Catalog No. 77, rumah penjual',
    ]);

    $response = get(route('catalog.index'))->assertOk();

    ListingPrivacyHelper::assertNoPrivateListingData($response, [
        'Jl. Rahasia Catalog No. 77, Pekanbaru',
        'MHKA7777777777777',
        'Jl. Rahasia Catalog No. 77, rumah penjual',
    ]);

    $response->assertSee('CAT-T06 privacy vehicle 999');
});

it('CAT-T12 shows the inspected badge only for badged listings', function () {
    Listing::factory()->active()->create([
        'title' => 'CAT-T12 badged vehicle 999',
        'is_inspected' => true,
        'last_inspected_at' => now(),
    ]);
    Listing::factory()->active()->create([
        'title' => 'CAT-T12 plain vehicle 999',
        'is_inspected' => false,
    ]);

    $content = get(route('catalog.index'))->assertOk()->getContent();

    expect(substr_count($content, __('ui.badges.inspected')))->toBe(1);
});

it('CAT-T11 keeps the catalog query count flat as items grow', function () {
    Listing::factory()->active()->count(5)->withPhotos(1)->create();

    DB::flushQueryLog();
    DB::enableQueryLog();
    get(route('catalog.index'))->assertOk();
    $small = count(DB::getQueryLog());

    Listing::factory()->active()->count(25)->withPhotos(1)->create();

    DB::flushQueryLog();
    get(route('catalog.index'))->assertOk();
    $large = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($large)->toBe($small);
});

it('CAT-R6 never writes listing or photo data while browsing the catalog', function () {
    $listing = Listing::factory()->active()->create([
        'title' => 'CAT-R6 read-only vehicle 999',
    ]);
    ListingPhoto::factory()->cover()->create(['listing_id' => $listing->id]);

    $listingCount = Listing::count();
    $photoCount = ListingPhoto::count();
    $fingerprint = Listing::query()->selectRaw('MAX(updated_at) as latest')->value('latest');

    get(route('catalog.index', ['q' => 'read-only', 'sort' => 'cheapest', 'page' => 1]))->assertOk();
    get(route('catalog.index', ['page' => 99, 'sort' => 'hack', 'price_min' => '-1']))->assertRedirect();

    expect(Listing::count())->toBe($listingCount)
        ->and(ListingPhoto::count())->toBe($photoCount)
        ->and(Listing::query()->selectRaw('MAX(updated_at) as latest')->value('latest'))->toEqual($fingerprint);
});
