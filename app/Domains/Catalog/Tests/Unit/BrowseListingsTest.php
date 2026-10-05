<?php

declare(strict_types=1);

use App\Domains\Catalog\Actions\BrowseListings;
use App\Domains\Catalog\DTOs\CatalogCard;
use App\Domains\Catalog\DTOs\CatalogFilter;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('CAT-T01 returns only publicly visible statuses through the catalog query', function () {
    foreach (['pending_review', 'active', 'rejected', 'in_inspection', 'in_transaction', 'sold', 'inactive'] as $status) {
        Listing::factory()->create([
            'status' => $status,
            'title' => "Status vehicle {$status} 12345",
        ]);
    }

    $results = app(BrowseListings::class)->handle(new CatalogFilter);

    $titles = collect($results->items())->map(fn (CatalogCard $card) => $card->title)->all();

    expect($results->total())->toBe(3)
        ->and($titles)->toContain('Status vehicle active 12345')
        ->and($titles)->toContain('Status vehicle in_inspection 12345')
        ->and($titles)->toContain('Status vehicle in_transaction 12345');
});

it('CAT-T06 never selects private columns in the catalog query', function () {
    $forbidden = ['lat', 'lng', 'address_full', 'chassis_number', 'inspection_place'];

    foreach ($forbidden as $column) {
        expect(BrowseListings::SAFE_COLUMNS)->not->toContain($column);
    }

    Listing::factory()->active()->create([
        'lat' => 0.5071000,
        'lng' => 101.4478000,
        'address_full' => 'Jl. Rahasia No. 99, Pekanbaru',
        'chassis_number' => 'MHKA9999999999999',
        'inspection_place' => 'Jl. Rahasia No. 99, rumah penjual',
    ]);

    DB::enableQueryLog();
    app(BrowseListings::class)->handle(new CatalogFilter);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $selects = implode(' ', array_column($queries, 'query'));

    foreach ($forbidden as $column) {
        expect($selects)->not->toContain($column);
    }
});

it('CAT-T02 filters listings by keyword across title, brand, and model', function () {
    Listing::factory()->active()->create(['title' => 'Toyota Avanza 1.3 G 2018 Terawat', 'brand' => 'Toyota', 'model' => 'Avanza']);
    Listing::factory()->active()->create(['title' => 'Honda Brio Satya E 2020 Mulus', 'brand' => 'Honda', 'model' => 'Brio']);
    Listing::factory()->active()->create(['title' => 'Honda Vario 150 CBS 2021 Irit', 'brand' => 'Honda', 'model' => 'Vario 150']);

    $results = app(BrowseListings::class)->handle(CatalogFilter::fromQuery(['q' => 'brio']));

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toContain('Brio');
});

it('CAT-T02 treats LIKE wildcards in keywords as literal text', function () {
    Listing::factory()->active()->create(['title' => 'Promo diskon 50% khusus bulan ini saja']);
    Listing::factory()->active()->create(['title' => 'Toyota Avanza 1.3 G 2018 Terawat Standar']);

    $percent = app(BrowseListings::class)->handle(CatalogFilter::fromQuery(['q' => '%']));

    expect($percent->total())->toBe(1)
        ->and($percent->items()[0]->title)->toContain('50%');

    $underscore = app(BrowseListings::class)->handle(CatalogFilter::fromQuery(['q' => '_']));

    expect($underscore->total())->toBe(0);
});

it('CAT-T02 combines type, brand, price, year, area, and inspected filters', function () {
    Listing::factory()->motorcycle()->active()->create([
        'title' => 'Honda Vario target kombinasi',
        'brand' => 'Honda',
        'price' => 20_000_000,
        'year' => 2021,
        'area_name' => 'Tampan',
        'is_inspected' => true,
        'last_inspected_at' => now(),
    ]);
    Listing::factory()->car()->active()->create([
        'title' => 'Toyota Avanza bukan target',
        'brand' => 'Toyota',
        'price' => 150_000_000,
        'year' => 2019,
        'area_name' => 'Sukajadi',
    ]);

    $results = app(BrowseListings::class)->handle(CatalogFilter::fromQuery([
        'type' => 'motorcycle',
        'brand' => 'hon',
        'price_min' => '10000000',
        'price_max' => '30000000',
        'year_min' => '2020',
        'year_max' => '2022',
        'area' => 'tamp',
        'inspected' => 'inspected',
    ]));

    expect($results->total())->toBe(1)
        ->and($results->items()[0]->title)->toBe('Honda Vario target kombinasi');
});

it('CAT-T03 sorts listings newest, cheapest, priciest, and lowest mileage', function () {
    $cheapOld = Listing::factory()->active()->create([
        'title' => 'CAT-T03 cheap old high km',
        'price' => 50_000_000,
        'odometer_km' => 100_000,
        'created_at' => now()->subDays(3),
    ]);
    $priceyNew = Listing::factory()->active()->create([
        'title' => 'CAT-T03 pricey new low km',
        'price' => 200_000_000,
        'odometer_km' => 10_000,
        'created_at' => now()->subDay(),
    ]);
    $mid = Listing::factory()->active()->create([
        'title' => 'CAT-T03 mid newest mid km',
        'price' => 120_000_000,
        'odometer_km' => 50_000,
        'created_at' => now(),
    ]);

    $titles = fn (string $sort): array => array_map(
        fn (CatalogCard $card) => $card->title,
        app(BrowseListings::class)->handle(CatalogFilter::fromQuery(['sort' => $sort]))->items()
    );

    expect($titles('newest'))->toBe([$mid->title, $priceyNew->title, $cheapOld->title])
        ->and($titles('cheapest'))->toBe([$cheapOld->title, $mid->title, $priceyNew->title])
        ->and($titles('priciest'))->toBe([$priceyNew->title, $mid->title, $cheapOld->title])
        ->and($titles('lowest_km'))->toBe([$priceyNew->title, $mid->title, $cheapOld->title]);
});

it('CAT-T04 paginates twelve cards per page with a correct total', function () {
    Listing::factory()->active()->count(13)->create();

    $first = app(BrowseListings::class)->handle(CatalogFilter::fromQuery(['page' => '1']));
    $second = app(BrowseListings::class)->handle(CatalogFilter::fromQuery(['page' => '2']));

    expect($first->total())->toBe(13)
        ->and($first->perPage())->toBe(12)
        ->and(count($first->items()))->toBe(12)
        ->and(count($second->items()))->toBe(1);
});

it('CAT-T12 hides the inspected badge when the listing badge was revoked', function () {
    $revoked = Listing::factory()->active()->create([
        'title' => 'CAT-T12 revoked badge vehicle',
        'is_inspected' => false,
        'last_inspected_at' => now()->subMonth(),
    ]);
    $badged = Listing::factory()->active()->create([
        'title' => 'CAT-T12 badged vehicle',
        'is_inspected' => true,
        'last_inspected_at' => now(),
    ]);

    $cards = collect(app(BrowseListings::class)->handle(new CatalogFilter)->items())
        ->keyBy(fn (CatalogCard $card) => $card->id);

    expect($cards[$revoked->id]->inspected)->toBeFalse()
        ->and($cards[$badged->id]->inspected)->toBeTrue();
});
