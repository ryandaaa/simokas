<?php

declare(strict_types=1);

use App\Domains\Listing\Enums\Transmission;
use Tests\TestCase;

uses(TestCase::class);

it('has expected transmission cases and labels', function () {
    expect(Transmission::Manual->value)->toBe('manual')
        ->and(Transmission::Automatic->value)->toBe('automatic')
        ->and(Transmission::Manual->label())->toBe('Manual')
        ->and(Transmission::Automatic->label())->toBe('Otomatis');
});
