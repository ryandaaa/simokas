<?php

declare(strict_types=1);

use App\Domains\Catalog\DTOs\CatalogFilter;
use Tests\TestCase;

uses(TestCase::class);

it('CAT-T05 defaults every invalid query parameter to a safe value', function (array $query, callable $assertions) {
    $filter = CatalogFilter::fromQuery($query);

    $assertions($filter);
})->with([
    'page as string' => [
        ['page' => 'abc'],
        fn (CatalogFilter $filter) => expect($filter->page)->toBe(1),
    ],
    'page as array' => [
        ['page' => ['2']],
        fn (CatalogFilter $filter) => expect($filter->page)->toBe(1),
    ],
    'negative page' => [
        ['page' => '-3'],
        fn (CatalogFilter $filter) => expect($filter->page)->toBe(1),
    ],
    'unknown sort' => [
        ['sort' => 'hack'],
        fn (CatalogFilter $filter) => expect($filter->sort)->toBe('newest'),
    ],
    'sort as array' => [
        ['sort' => ['cheapest']],
        fn (CatalogFilter $filter) => expect($filter->sort)->toBe('newest'),
    ],
    'unknown type' => [
        ['type' => 'spaceship'],
        fn (CatalogFilter $filter) => expect($filter->type)->toBeNull(),
    ],
    'type as array' => [
        ['type' => ['car']],
        fn (CatalogFilter $filter) => expect($filter->type)->toBeNull(),
    ],
    'negative price_min' => [
        ['price_min' => '-1'],
        fn (CatalogFilter $filter) => expect($filter->priceMin)->toBeNull(),
    ],
    'price_min above price_max' => [
        ['price_min' => '200000000', 'price_max' => '100000000'],
        fn (CatalogFilter $filter) => expect($filter->priceMin)->toBeNull()
            ->and($filter->priceMax)->toBeNull(),
    ],
    'year out of range' => [
        ['year_min' => '1800', 'year_max' => '9999'],
        fn (CatalogFilter $filter) => expect($filter->yearMin)->toBeNull()
            ->and($filter->yearMax)->toBeNull(),
    ],
    'year_min above year_max' => [
        ['year_min' => '2022', 'year_max' => '2018'],
        fn (CatalogFilter $filter) => expect($filter->yearMin)->toBeNull()
            ->and($filter->yearMax)->toBeNull(),
    ],
    'keyword as array' => [
        ['q' => ['avanza']],
        fn (CatalogFilter $filter) => expect($filter->keyword)->toBeNull(),
    ],
    'unknown inspected option' => [
        ['inspected' => 'maybe'],
        fn (CatalogFilter $filter) => expect($filter->inspected)->toBe('all'),
    ],
]);

it('CAT-T05 trims, truncates, and keeps valid filter values', function () {
    $filter = CatalogFilter::fromQuery([
        'q' => '  Avanza  ',
        'type' => 'car',
        'brand' => 'Toyota',
        'price_min' => '50000000',
        'price_max' => '200000000',
        'year_min' => '2018',
        'year_max' => '2022',
        'area' => ' Tampan ',
        'inspected' => 'inspected',
        'sort' => 'cheapest',
        'page' => '3',
    ]);

    expect($filter->keyword)->toBe('Avanza')
        ->and($filter->type)->toBe('car')
        ->and($filter->brand)->toBe('Toyota')
        ->and($filter->priceMin)->toBe(50_000_000)
        ->and($filter->priceMax)->toBe(200_000_000)
        ->and($filter->yearMin)->toBe(2018)
        ->and($filter->yearMax)->toBe(2022)
        ->and($filter->area)->toBe('Tampan')
        ->and($filter->inspected)->toBe('inspected')
        ->and($filter->sort)->toBe('cheapest')
        ->and($filter->page)->toBe(3);
});

it('CAT-T05 truncates overlong keywords instead of failing', function () {
    $filter = CatalogFilter::fromQuery(['q' => str_repeat('a', 500)]);

    expect($filter->keyword)->not->toBeNull()
        ->and(mb_strlen((string) $filter->keyword))->toBe(CatalogFilter::KEYWORD_MAX_LENGTH);
});

it('CAT-T05 excludes defaults and page from pagination query parameters', function () {
    $filter = CatalogFilter::fromQuery([]);

    expect($filter->toQuery())->toBe([]);

    $filtered = CatalogFilter::fromQuery(['q' => 'Avanza', 'sort' => 'cheapest', 'page' => '2']);

    expect($filtered->toQuery())->toBe(['q' => 'Avanza', 'sort' => 'cheapest']);
});
