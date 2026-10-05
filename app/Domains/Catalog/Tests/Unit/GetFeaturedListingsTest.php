<?php

declare(strict_types=1);

use App\Domains\Catalog\Actions\BrowseListings;
use App\Domains\Catalog\Actions\GetFeaturedListings;
use App\Domains\Catalog\DTOs\CatalogCard;
use App\Domains\Listing\Models\Listing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('CAT-T13 returns only active listings as featured cards', function () {
    Listing::factory()->create(['status' => 'active', 'title' => 'CAT unit featured active 999']);
    Listing::factory()->create(['status' => 'in_inspection', 'title' => 'CAT unit featured inspection 999']);
    Listing::factory()->create(['status' => 'in_transaction', 'title' => 'CAT unit featured transaction 999']);
    Listing::factory()->create(['status' => 'sold', 'title' => 'CAT unit featured sold 999']);

    $cards = app(GetFeaturedListings::class)->handle();

    expect($cards)->toHaveCount(1)
        ->and($cards[0])->toBeInstanceOf(CatalogCard::class)
        ->and($cards[0]->title)->toBe('CAT unit featured active 999');
});

it('CAT-T13 returns at most six featured cards ordered newest first', function () {
    for ($i = 1; $i <= 8; $i++) {
        Listing::factory()->create([
            'status' => 'active',
            'title' => "CAT unit newest vehicle {$i} 999",
            'created_at' => now()->addMinutes($i),
        ]);
    }

    $cards = app(GetFeaturedListings::class)->handle();

    expect($cards)->toHaveCount(6)
        ->and($cards[0]->title)->toBe('CAT unit newest vehicle 8 999')
        ->and($cards[5]->title)->toBe('CAT unit newest vehicle 3 999');
});

it('CAT-T13 returns an empty list when no active listing exists', function () {
    Listing::factory()->create(['status' => 'sold', 'title' => 'CAT unit sold only 999']);

    expect(app(GetFeaturedListings::class)->handle())->toBe([]);
});

it('CAT-T13 never selects private columns for featured listings', function () {
    foreach (['lat', 'lng', 'address_full', 'chassis_number', 'inspection_place'] as $private) {
        expect(BrowseListings::SAFE_COLUMNS)->not->toContain($private);
    }

    Listing::factory()->create([
        'status' => 'active',
        'title' => 'CAT unit safe columns 999',
        'address_full' => 'Jl. Rahasia Unit No. 77, Pekanbaru',
        'chassis_number' => 'MHKC7777777777777',
    ]);

    $cards = app(GetFeaturedListings::class)->handle();

    expect($cards)->toHaveCount(1)
        ->and((array) $cards[0])->not->toHaveKeys(['lat', 'lng', 'address_full', 'chassis_number', 'inspection_place']);
});
