<?php

declare(strict_types=1);

namespace App\Domains\Inspection\Tests\Unit;

use App\Domains\Inspection\DTOs\OpenJobFilter;
use Tests\TestCase;

uses(TestCase::class);

it('constructs OpenJobFilter with defaults and sanitizes input array', function () {
    $defaultFilter = new OpenJobFilter;
    expect($defaultFilter->vehicleType)->toBeNull()
        ->and($defaultFilter->area)->toBeNull()
        ->and($defaultFilter->feeMin)->toBeNull()
        ->and($defaultFilter->feeMax)->toBeNull()
        ->and($defaultFilter->sort)->toBe(OpenJobFilter::SORT_LATEST)
        ->and($defaultFilter->page)->toBe(1);

    $sanitized = OpenJobFilter::fromArray([
        'vehicle_type' => ' CAR ',
        'area' => '  Tampan  ',
        'fee_min' => '50000',
        'fee_max' => '100000',
        'sort' => OpenJobFilter::SORT_HIGHEST_FEE,
        'page' => '3',
    ]);

    expect($sanitized->vehicleType)->toBe('car')
        ->and($sanitized->area)->toBe('Tampan')
        ->and($sanitized->feeMin)->toBe(50000)
        ->and($sanitized->feeMax)->toBe(100000)
        ->and($sanitized->sort)->toBe(OpenJobFilter::SORT_HIGHEST_FEE)
        ->and($sanitized->page)->toBe(3);
});

it('falls back to default sort and page on invalid values in fromArray', function () {
    $filter = OpenJobFilter::fromArray([
        'sort' => 'invalid_sort_string',
        'page' => -5,
        'fee_min' => -100,
    ]);

    expect($filter->sort)->toBe(OpenJobFilter::SORT_LATEST)
        ->and($filter->page)->toBe(1)
        ->and($filter->feeMin)->toBe(0);
});
