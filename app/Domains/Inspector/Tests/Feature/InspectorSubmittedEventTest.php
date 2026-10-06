<?php

declare(strict_types=1);

namespace App\Domains\Inspector\Tests\Feature;

use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Actions\CreateInspectorProfile;
use App\Domains\Inspector\Enums\VehicleType;
use App\Domains\Inspector\Events\InspectorSubmitted;
use App\Domains\Inspector\Exceptions\InvalidInspectorProfileException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('dispatches InspectorSubmitted exactly once when onboarding verification is submitted', function (): void {
    Event::fake([InspectorSubmitted::class]);

    $user = User::factory()->inspector()->create();

    app(CreateInspectorProfile::class)->handle($user, [
        'vehicle_types' => [VehicleType::Car->value],
        'experience_years' => 5,
    ]);

    Event::assertDispatched(InspectorSubmitted::class, 1);
});

it('carries the profile and user ids in the InspectorSubmitted payload', function (): void {
    Event::fake([InspectorSubmitted::class]);

    $user = User::factory()->inspector()->create();

    $profile = app(CreateInspectorProfile::class)->handle($user, [
        'vehicle_types' => [VehicleType::Motorcycle->value],
        'experience_years' => 3,
    ]);

    Event::assertDispatched(InspectorSubmitted::class, function (InspectorSubmitted $event) use ($profile, $user): bool {
        return $event->inspectorProfileId === $profile->id
            && $event->userId === $user->id;
    });
});

it('does not dispatch InspectorSubmitted when submission fails validation', function (): void {
    Event::fake([InspectorSubmitted::class]);

    $user = User::factory()->inspector()->create();

    expect(fn (): mixed => app(CreateInspectorProfile::class)->handle($user, [
        'vehicle_types' => [],
        'experience_years' => 5,
    ]))->toThrow(InvalidInspectorProfileException::class);

    Event::assertNotDispatched(InspectorSubmitted::class);
});

it('does not dispatch InspectorSubmitted when a non-inspector submits', function (): void {
    Event::fake([InspectorSubmitted::class]);

    $user = User::factory()->create();

    expect(fn (): mixed => app(CreateInspectorProfile::class)->handle($user, [
        'vehicle_types' => [VehicleType::Car->value],
        'experience_years' => 5,
    ]))->toThrow(InvalidInspectorProfileException::class);

    Event::assertNotDispatched(InspectorSubmitted::class);
});
