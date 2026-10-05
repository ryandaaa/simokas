<?php

declare(strict_types=1);

use App\Domains\Setting\DTOs\SettingsData;
use App\Domains\Setting\Enums\SettingKey;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('converts SettingsData DTO to array', function () {
    $now = Carbon::parse('2026-10-05 10:00:00');

    $dto = new SettingsData(
        inspectionFeeCar: 50000,
        inspectionFeeMotorcycle: 15000,
        platformFeePct: 10,
        saleCommissionPct: 3,
        autoAcceptDays: 3,
        paidJobTimeoutDays: 7,
        updatedBy: 'user-uuid-123',
        updatedByName: 'Admin User',
        updatedAt: $now,
    );

    $array = $dto->toArray();

    expect($array)->toBe([
        'inspection_fee_car' => 50000,
        'inspection_fee_motorcycle' => 15000,
        'platform_fee_pct' => 10,
        'sale_commission_pct' => 3,
        'auto_accept_days' => 3,
        'paid_job_timeout_days' => 7,
        'updated_by' => 'user-uuid-123',
        'updated_by_name' => 'Admin User',
        'updated_at' => $now->toIso8601String(),
    ]);
});

it('returns validation rules for each SettingKey', function (SettingKey $key) {
    $rules = $key->validationRules();

    expect($rules)->toBeArray()
        ->and($rules)->not->toBeEmpty();
})->with(SettingKey::cases());
