<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\CreateInspectorProfile;
use App\Domains\Inspector\Enums\VehicleType;
use App\Domains\Inspector\Enums\VerificationStatus;
use App\Domains\Inspector\Exceptions\InvalidInspectorProfileException;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\assertDatabaseHas;

uses(TestCase::class, RefreshDatabase::class);

it('creates inspector profile with valid data and pending status', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = new CreateInspectorProfile;

    $profile = $action->handle($inspector, [
        'vehicle_types' => [VehicleType::Car->value, VehicleType::Motorcycle->value],
        'experience_years' => 5,
    ]);

    expect($profile)->toBeInstanceOf(InspectorProfile::class)
        ->and($profile->user_id)->toBe($inspector->id)
        ->and($profile->vehicle_types)->toEqualCanonicalizing(['car', 'motorcycle'])
        ->and($profile->experience_years)->toBe(5)
        ->and($profile->verification_status)->toBe(VerificationStatus::Pending);

    assertDatabaseHas('inspector_profiles', [
        'user_id' => $inspector->id,
        'experience_years' => 5,
        'verification_status' => 'pending',
    ]);
});

it('updates existing profile idempotently without creating duplicate records', function () {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = new CreateInspectorProfile;

    $profile1 = $action->handle($inspector, [
        'vehicle_types' => [VehicleType::Car->value],
        'experience_years' => 3,
    ]);

    $profile2 = $action->handle($inspector, [
        'vehicle_types' => [VehicleType::Car->value, VehicleType::Motorcycle->value],
        'experience_years' => 6,
    ]);

    expect($profile2->id)->toBe($profile1->id)
        ->and($profile2->experience_years)->toBe(6)
        ->and($profile2->vehicle_types)->toEqualCanonicalizing(['car', 'motorcycle']);

    expect(InspectorProfile::query()->where('user_id', $inspector->id)->count())->toBe(1);
});

it('rejects profile creation for regular users who are not inspectors', function () {
    $user = User::factory()->user()->active()->create();
    $action = new CreateInspectorProfile;

    expect(fn () => $action->handle($user, [
        'vehicle_types' => [VehicleType::Car->value],
        'experience_years' => 2,
    ]))->toThrow(InvalidInspectorProfileException::class);
});

it('rejects invalid experience years', function (int $years) {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = new CreateInspectorProfile;

    expect(fn () => $action->handle($inspector, [
        'vehicle_types' => [VehicleType::Car->value],
        'experience_years' => $years,
    ]))->toThrow(InvalidInspectorProfileException::class);
})->with([-1, -10, 51, 100]);

it('rejects empty or invalid vehicle types', function ($invalidVehicleTypes) {
    $inspector = User::factory()->inspector()->pending()->create();
    $action = new CreateInspectorProfile;

    expect(fn () => $action->handle($inspector, [
        'vehicle_types' => $invalidVehicleTypes,
        'experience_years' => 3,
    ]))->toThrow(InvalidInspectorProfileException::class);
})->with([
    'empty array' => [[]],
    'invalid string values' => [['plane', 'boat']],
]);
