<?php

declare(strict_types=1);

use App\Domains\Setting\Enums\SettingKey;
use Tests\TestCase;

uses(TestCase::class);

it('defines all required setting keys per PRD 18.17 and decision 0002', function () {
    $keys = array_map(fn (SettingKey $k) => $k->value, SettingKey::cases());

    expect($keys)->toBe([
        'inspection_fee_car',
        'inspection_fee_motorcycle',
        'platform_fee_pct',
        'sale_commission_pct',
        'auto_accept_days',
        'paid_job_timeout_days',
    ]);
});

it('provides expected default values per issue requirements', function (SettingKey $key, int $expectedDefault) {
    expect($key->defaultValue())->toBe($expectedDefault);
})->with([
    'inspection_fee_car default' => [SettingKey::InspectionFeeCar, 50000],
    'inspection_fee_motorcycle default' => [SettingKey::InspectionFeeMotorcycle, 15000],
    'platform_fee_pct default' => [SettingKey::PlatformFeePct, 10],
    'sale_commission_pct default' => [SettingKey::SaleCommissionPct, 3],
    'auto_accept_days default' => [SettingKey::AutoAcceptDays, 3],
    'paid_job_timeout_days default' => [SettingKey::PaidJobTimeoutDays, 7],
]);

it('validates values according to business rule ADM-R3', function (SettingKey $key, mixed $validValue, mixed $invalidValue) {
    expect($key->isValid($validValue))->toBeTrue()
        ->and($key->isValid($invalidValue))->toBeFalse();
})->with([
    'car fee positive integer' => [SettingKey::InspectionFeeCar, 50000, 0],
    'car fee rejects negative' => [SettingKey::InspectionFeeCar, 1, -500],
    'motorcycle fee positive integer' => [SettingKey::InspectionFeeMotorcycle, 15000, -100],
    'motorcycle fee rejects zero' => [SettingKey::InspectionFeeMotorcycle, 15000, 0],
    'platform fee 0-100 range valid' => [SettingKey::PlatformFeePct, 0, -1],
    'platform fee max 100' => [SettingKey::PlatformFeePct, 100, 101],
    'sale commission 0-100 range valid' => [SettingKey::SaleCommissionPct, 3, 150],
    'sale commission rejects negative' => [SettingKey::SaleCommissionPct, 0, -5],
    'auto accept days min 1' => [SettingKey::AutoAcceptDays, 1, 0],
    'auto accept days rejects negative' => [SettingKey::AutoAcceptDays, 3, -1],
    'paid job timeout days min 1' => [SettingKey::PaidJobTimeoutDays, 7, 0],
    'rejects non-numeric string' => [SettingKey::InspectionFeeCar, 10000, 'abc'],
]);

it('provides Indonesian labels, hints, and units', function (SettingKey $key) {
    expect($key->label())->toBeString()->and($key->label())->not->toBeEmpty()
        ->and($key->hint())->toBeString()->and($key->hint())->not->toBeEmpty()
        ->and($key->unit())->toBeString()->and($key->unit())->not->toBeEmpty();
})->with(SettingKey::cases());
