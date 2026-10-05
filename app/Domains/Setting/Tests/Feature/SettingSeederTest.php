<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Setting\Database\Seeders\SettingSeeder;
use App\Domains\Setting\Enums\SettingKey;
use App\Domains\Setting\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds all default settings properly', function () {
    (new SettingSeeder)->run();

    expect(AppSetting::count())->toBe(6);

    expect(AppSetting::where('key', 'inspection_fee_car')->first()?->intValue())->toBe(50000)
        ->and(AppSetting::where('key', 'inspection_fee_motorcycle')->first()?->intValue())->toBe(15000)
        ->and(AppSetting::where('key', 'platform_fee_pct')->first()?->intValue())->toBe(10)
        ->and(AppSetting::where('key', 'sale_commission_pct')->first()?->intValue())->toBe(3)
        ->and(AppSetting::where('key', 'auto_accept_days')->first()?->intValue())->toBe(3)
        ->and(AppSetting::where('key', 'paid_job_timeout_days')->first()?->intValue())->toBe(7);
});

it('is idempotent and can be run multiple times safely', function () {
    (new SettingSeeder)->run();
    $firstRunCount = AppSetting::count();

    (new SettingSeeder)->run();
    $secondRunCount = AppSetting::count();

    expect($secondRunCount)->toBe($firstRunCount)
        ->and($secondRunCount)->toBe(6);
});

it('does not overwrite settings modified by admin when re-seeded', function () {
    $admin = User::factory()->admin()->create();

    // Initial seed
    (new SettingSeeder)->run();

    // Admin updates a setting
    /** @var AppSetting $carSetting */
    $carSetting = AppSetting::where('key', SettingKey::InspectionFeeCar->value)->firstOrFail();
    $carSetting->update([
        'value' => 80000,
        'updated_by' => $admin->id,
    ]);

    // Re-seed
    (new SettingSeeder)->run();

    // Modified setting should still have admin value
    expect($carSetting->refresh()->intValue())->toBe(80000)
        ->and($carSetting->updated_by)->toBe($admin->id);
});
