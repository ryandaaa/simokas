<?php

declare(strict_types=1);

use App\Domains\Identity\Models\User;
use App\Domains\Setting\Policies\SettingPolicy;
use Tests\TestCase;

uses(TestCase::class);

it('allows only admin to view and update settings in SettingPolicy', function (string $role, bool $expected) {
    $user = User::factory()->make(['role' => $role]);
    $policy = new SettingPolicy;

    expect($policy->viewAny($user))->toBe($expected)
        ->and($policy->view($user))->toBe($expected)
        ->and($policy->update($user))->toBe($expected);
})->with([
    'admin role' => ['admin', true],
    'inspector role' => ['inspector', false],
    'user role' => ['user', false],
]);
