<?php

declare(strict_types=1);

use App\Domains\Listing\Rules\WithinPekanbaru;
use App\Domains\Listing\ValueObjects\PekanbaruBoundary;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

uses(TestCase::class);

function pekanbaruValidator(mixed $lat, mixed $lng): Illuminate\Validation\Validator
{
    return Validator::make(
        ['lat' => $lat, 'lng' => $lng],
        [
            'lat' => ['required', new WithinPekanbaru('lat', 'lng')],
            'lng' => ['required', new WithinPekanbaru('lng', 'lat')],
        ]
    );
}

it('LST-T03 accepts points inside Pekanbaru (dataset)', function (float $lat, float $lng) {
    expect(pekanbaruValidator($lat, $lng)->passes())->toBeTrue();
})->with([
    'city centre (Sukajadi)' => [0.5071, 101.4478],
    'Tampan' => [0.4700, 101.3800],
    'Marpoyan Damai' => [0.4800, 101.4200],
    'Rumbai' => [0.6000, 101.4500],
    'Tenayan Raya' => [0.5200, 101.5500],
    'south edge of box' => [PekanbaruBoundary::MIN_LAT, 101.4500],
    'north edge of box' => [PekanbaruBoundary::MAX_LAT, 101.4500],
    'west edge of box' => [0.5100, PekanbaruBoundary::MIN_LNG],
    'east edge of box' => [0.5100, PekanbaruBoundary::MAX_LNG],
]);

it('LST-T03 rejects points outside Pekanbaru with a clear message (dataset)', function (float $lat, float $lng) {
    $validator = pekanbaruValidator($lat, $lng);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('lat'))->toContain('Pekanbaru');
})->with([
    'Jakarta' => [-6.2000, 106.8000],
    'just north of boundary' => [PekanbaruBoundary::MAX_LAT + 0.01, 101.4500],
    'just south of boundary' => [PekanbaruBoundary::MIN_LAT - 0.01, 101.4500],
    'just west of boundary' => [0.5100, PekanbaruBoundary::MIN_LNG - 0.01],
    'just east of boundary' => [0.5100, PekanbaruBoundary::MAX_LNG + 0.01],
    'null island (0,0)' => [0.0, 0.0],
]);

it('rejects non-numeric, NaN, infinite, and empty coordinates', function (mixed $value) {
    expect(pekanbaruValidator($value, 101.4478)->fails())->toBeTrue();
})->with([
    'null' => [null],
    'empty string' => [''],
    'plain text' => ['abc'],
    'NaN string' => ['NaN'],
    'NaN float' => [NAN],
    'positive infinity' => [INF],
    'array' => [[0.5]],
]);

it('rejects latitude outside -90..90 and longitude outside -180..180', function (string $axis, mixed $value, mixed $other) {
    [$lat, $lng] = $axis === 'lat' ? [$value, $other] : [$other, $value];

    $validator = pekanbaruValidator($lat, $lng);

    expect($validator->fails())->toBeTrue();
})->with([
    'lat above 90' => ['lat', 90.0001, 101.4478],
    'lat below -90' => ['lat', -90.0001, 101.4478],
    'lng above 180' => ['lng', 180.0001, 0.5071],
    'lng below -180' => ['lng', -180.0001, 0.5071],
    'lng as text' => ['lng', 'timur', 0.5071],
]);
