<?php

declare(strict_types=1);

use App\Domains\Admin\Livewire\Settings;
use App\Domains\Identity\Models\User;
use App\Domains\Setting\Contracts\SettingsReader;
use App\Domains\Setting\Contracts\UpdatesSettings;
use App\Domains\Setting\Database\Seeders\SettingSeeder;
use App\Domains\Setting\Exceptions\InvalidSettingValueException;
use App\Domains\Setting\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

it('renders admin settings component with current settings and audit info', function () {
    (new SettingSeeder)->run();

    $admin = User::factory()->admin()->create(['name' => 'Budi Administrator']);
    actingAs($admin);

    Livewire::test(Settings::class)
        ->assertOk()
        ->assertSee(__('admin.settings.title'))
        ->assertSee(__('admin.settings.never_updated'))
        ->assertSet('form.inspection_fee_car', 50000)
        ->assertSet('form.inspection_fee_motorcycle', 15000)
        ->assertSet('form.platform_fee_pct', 10)
        ->assertSet('form.sale_commission_pct', 3)
        ->assertSet('form.auto_accept_days', 3)
        ->assertSet('form.paid_job_timeout_days', 7);
});

it('ADM-T05 opens confirmation modal on valid inputs and saves settings atomically', function () {
    (new SettingSeeder)->run();

    $admin = User::factory()->admin()->create(['name' => 'Siti Administrator']);
    actingAs($admin);

    $component = Livewire::test(Settings::class)
        ->set('form.inspection_fee_car', 65000)
        ->set('form.inspection_fee_motorcycle', 25000)
        ->set('form.platform_fee_pct', 15)
        ->set('form.sale_commission_pct', 5)
        ->set('form.auto_accept_days', 4)
        ->set('form.paid_job_timeout_days', 10)
        ->call('confirmSave')
        ->assertHasNoErrors()
        ->assertSet('showConfirmModal', true)
        ->assertDispatched('open-modal', 'confirm-save-settings');

    $component->call('save')
        ->assertHasNoErrors()
        ->assertSet('showConfirmModal', false)
        ->assertRedirect(route('dashboard'))
        ->assertDispatched('close-modal', 'confirm-save-settings');

    expect(session('success'))->toBe(__('admin.settings.success'));

    /** @var SettingsReader $reader */
    $reader = app(SettingsReader::class);
    expect($reader->getInspectionFeeCar())->toBe(65000)
        ->and($reader->getInspectionFeeMotorcycle())->toBe(25000)
        ->and($reader->getPlatformFeePct())->toBe(15)
        ->and($reader->getSaleCommissionPct())->toBe(5)
        ->and($reader->getAutoAcceptDays())->toBe(4)
        ->and($reader->getPaidJobTimeoutDays())->toBe(10);

    // Verify DB updated_by
    /** @var AppSetting $setting */
    $setting = AppSetting::where('key', 'inspection_fee_car')->firstOrFail();
    expect($setting->updated_by)->toBe($admin->id);
});

it('ADM-T05 rejects invalid inputs with field validation errors and does not save partially', function (string $field, mixed $invalidValue) {
    (new SettingSeeder)->run();

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    Livewire::test(Settings::class)
        ->set('form.inspection_fee_car', 70000) // changed valid value
        ->set("form.{$field}", $invalidValue)  // invalid value
        ->call('confirmSave')
        ->assertHasErrors(["form.{$field}"])
        ->assertSet('showConfirmModal', false);

    // Verify database was NOT changed (no partial save)
    /** @var AppSetting $setting */
    $setting = AppSetting::where('key', 'inspection_fee_car')->firstOrFail();
    expect($setting->intValue())->toBe(50000);
})->with([
    'car fee zero' => ['inspection_fee_car', 0],
    'car fee negative' => ['inspection_fee_car', -100],
    'car fee non-numeric' => ['inspection_fee_car', 'tidak_valid'],
    'motorcycle fee zero' => ['inspection_fee_motorcycle', 0],
    'motorcycle fee negative' => ['inspection_fee_motorcycle', -50],
    'platform pct negative' => ['platform_fee_pct', -1],
    'platform pct above 100' => ['platform_fee_pct', 105],
    'sale commission negative' => ['sale_commission_pct', -5],
    'sale commission above 100' => ['sale_commission_pct', 101],
    'auto accept days zero' => ['auto_accept_days', 0],
    'auto accept days negative' => ['auto_accept_days', -1],
    'paid job timeout days zero' => ['paid_job_timeout_days', 0],
    'paid job timeout days negative' => ['paid_job_timeout_days', -3],
]);

it('allows closing confirmation modal without saving', function () {
    (new SettingSeeder)->run();

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    Livewire::test(Settings::class)
        ->set('form.inspection_fee_car', 90000)
        ->call('confirmSave')
        ->assertSet('showConfirmModal', true)
        ->call('closeConfirmModal')
        ->assertSet('showConfirmModal', false)
        ->assertDispatched('close-modal', 'confirm-save-settings');

    // Database still unchanged
    /** @var AppSetting $setting */
    $setting = AppSetting::where('key', 'inspection_fee_car')->firstOrFail();
    expect($setting->intValue())->toBe(50000);
});

it('denies access to non-admin user via policy authorization', function () {
    $user = User::factory()->create(); // regular user
    actingAs($user);

    Livewire::test(Settings::class)
        ->assertForbidden();
});

it('handles InvalidSettingValueException gracefully during save', function () {
    (new SettingSeeder)->run();

    $admin = User::factory()->admin()->create();
    actingAs($admin);

    $mock = Mockery::mock(UpdatesSettings::class);
    $mock->shouldReceive('handle')
        ->once()
        ->andThrow(new InvalidSettingValueException('Terjadi kesalahan nilai pengaturan khusus.'));
    app()->instance(UpdatesSettings::class, $mock);

    Livewire::test(Settings::class)
        ->call('save')
        ->assertSet('errorMessage', 'Terjadi kesalahan nilai pengaturan khusus.')
        ->assertSet('showConfirmModal', false)
        ->assertDispatched('close-modal', 'confirm-save-settings');
});
