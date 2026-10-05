<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Setting\Actions\UpdateSettings;
use App\Domains\Setting\Contracts\UpdatesSettings;
use App\Domains\Setting\Exceptions\InvalidSettingValueException;
use App\Domains\Setting\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('updates all settings atomically with valid values and records audit info', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
    /** @var UpdateSettings $action */
    $action = app(UpdatesSettings::class);

    $validData = [
        'inspection_fee_car' => 60000,
        'inspection_fee_motorcycle' => 20000,
        'platform_fee_pct' => 12,
        'sale_commission_pct' => 4,
        'auto_accept_days' => 5,
        'paid_job_timeout_days' => 10,
    ];

    $action->handle($validData, $admin);

    expect(AppSetting::count())->toBe(6);

    $carFee = AppSetting::where('key', 'inspection_fee_car')->first();
    expect($carFee)->not->toBeNull()
        ->and($carFee->intValue())->toBe(60000)
        ->and($carFee->updated_by)->toBe($admin->id);

    $platformPct = AppSetting::where('key', 'platform_fee_pct')->first();
    expect($platformPct)->not->toBeNull()
        ->and($platformPct->intValue())->toBe(12)
        ->and($platformPct->updated_by)->toBe($admin->id);
});

it('ADM-T05 rejects invalid values with domain exception and does not perform partial save', function (string $invalidKey, mixed $invalidValue) {
    $admin = User::factory()->admin()->create();
    /** @var UpdateSettings $action */
    $action = app(UpdatesSettings::class);

    // Initial valid state
    $action->handle([
        'inspection_fee_car' => 50000,
        'inspection_fee_motorcycle' => 15000,
        'platform_fee_pct' => 10,
        'sale_commission_pct' => 3,
        'auto_accept_days' => 3,
        'paid_job_timeout_days' => 7,
    ], $admin);

    // New payload where only one field is invalid, and another is changed
    $newPayload = [
        'inspection_fee_car' => 99999, // changed valid value
        'inspection_fee_motorcycle' => 15000,
        'platform_fee_pct' => 10,
        'sale_commission_pct' => 3,
        'auto_accept_days' => 3,
        'paid_job_timeout_days' => 7,
    ];
    $newPayload[$invalidKey] = $invalidValue;

    expect(fn () => $action->handle($newPayload, $admin))
        ->toThrow(InvalidSettingValueException::class);

    // Verify all-or-nothing: inspection_fee_car was NOT changed to 99999
    expect(AppSetting::where('key', 'inspection_fee_car')->first()->intValue())->toBe(50000);
})->with([
    'car fee negative' => ['inspection_fee_car', -1000],
    'car fee zero' => ['inspection_fee_car', 0],
    'car fee string non-digit' => ['inspection_fee_car', 'tidak_valid'],
    'motorcycle fee zero' => ['inspection_fee_motorcycle', 0],
    'motorcycle fee negative' => ['inspection_fee_motorcycle', -500],
    'platform pct negative' => ['platform_fee_pct', -1],
    'platform pct above 100' => ['platform_fee_pct', 101],
    'sale commission negative' => ['sale_commission_pct', -1],
    'sale commission above 100' => ['sale_commission_pct', 150],
    'auto accept days zero' => ['auto_accept_days', 0],
    'auto accept days negative' => ['auto_accept_days', -2],
    'paid job timeout days zero' => ['paid_job_timeout_days', 0],
    'paid job timeout days negative' => ['paid_job_timeout_days', -5],
]);

it('rejects payload if any required setting key is missing', function () {
    $admin = User::factory()->admin()->create();
    /** @var UpdateSettings $action */
    $action = app(UpdatesSettings::class);

    $incompletePayload = [
        'inspection_fee_car' => 50000,
        'inspection_fee_motorcycle' => 15000,
        'platform_fee_pct' => 10,
        // missing sale_commission_pct
        'auto_accept_days' => 3,
        'paid_job_timeout_days' => 7,
    ];

    expect(fn () => $action->handle($incompletePayload, $admin))
        ->toThrow(InvalidSettingValueException::class);
});
