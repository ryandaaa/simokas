<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Setting\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates app setting with uuid and json value', function () {
    $user = User::factory()->admin()->create();

    $setting = AppSetting::create([
        'key' => 'inspection_fee_car',
        'value' => 50000,
        'updated_by' => $user->id,
    ]);

    expect($setting->id)->toBeString()
        ->and(Str::isUuid($setting->id))->toBeTrue()
        ->and($setting->key)->toBe('inspection_fee_car')
        ->and($setting->intValue())->toBe(50000)
        ->and($setting->updater)->not->toBeNull()
        ->and($setting->updater->id)->toBe($user->id);
});

it('correctly retrieves int value when value is array or scalar', function () {
    $settingScalar = AppSetting::create([
        'key' => 'fee_scalar',
        'value' => 25000,
    ]);

    $settingArray = AppSetting::create([
        'key' => 'fee_array',
        'value' => ['value' => 35000],
    ]);

    expect($settingScalar->intValue())->toBe(25000)
        ->and($settingArray->intValue())->toBe(35000);
});
