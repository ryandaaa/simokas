<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Domains\Setting\DTOs\SettingsData;
use App\Domains\Setting\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns default typed values when app_settings table is empty', function () {
    /** @var SettingsReader $reader */
    $reader = app(SettingsReader::class);

    expect($reader->getInspectionFeeCar())->toBe(50000)
        ->and($reader->getInspectionFeeMotorcycle())->toBe(15000)
        ->and($reader->getPlatformFeePct())->toBe(10)
        ->and($reader->getSaleCommissionPct())->toBe(3)
        ->and($reader->getAutoAcceptDays())->toBe(3)
        ->and($reader->getPaidJobTimeoutDays())->toBe(7);

    $all = $reader->all();
    expect($all)->toBeInstanceOf(SettingsData::class)
        ->and($all->inspectionFeeCar)->toBe(50000)
        ->and($all->inspectionFeeMotorcycle)->toBe(15000)
        ->and($all->platformFeePct)->toBe(10)
        ->and($all->saleCommissionPct)->toBe(3)
        ->and($all->autoAcceptDays)->toBe(3)
        ->and($all->paidJobTimeoutDays)->toBe(7)
        ->and($all->updatedBy)->toBeNull()
        ->and($all->updatedByName)->toBeNull()
        ->and($all->updatedAt)->toBeNull();
});

it('returns persisted typed values and audit metadata when app_settings table has data', function () {
    $admin = User::factory()->admin()->create(['name' => 'Super Admin']);

    AppSetting::create([
        'key' => 'inspection_fee_car',
        'value' => 75000,
        'updated_by' => $admin->id,
    ]);

    AppSetting::create([
        'key' => 'platform_fee_pct',
        'value' => 15,
        'updated_by' => $admin->id,
    ]);

    /** @var SettingsReader $reader */
    $reader = app(SettingsReader::class);

    expect($reader->getInspectionFeeCar())->toBe(75000)
        ->and($reader->getPlatformFeePct())->toBe(15)
        ->and($reader->getInspectionFeeMotorcycle())->toBe(15000); // fallback default

    $all = $reader->all();
    expect($all->inspectionFeeCar)->toBe(75000)
        ->and($all->platformFeePct)->toBe(15)
        ->and($all->updatedBy)->toBe($admin->id)
        ->and($all->updatedByName)->toBe('Super Admin')
        ->and($all->updatedAt)->not->toBeNull();
});
