<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('redirects unauthenticated guest to login when accessing admin settings route', function () {
    get(route('admin.settings'))
        ->assertRedirect(route('login'));
});

it('returns 401 unauthenticated for guest json request to admin settings route', function () {
    getJson(route('admin.settings'))
        ->assertStatus(401);
});

it('ADM-T01 returns 403 forbidden for non-admin roles and 200 ok for admin', function (string $roleName, int $expectedStatus) {
    /** @var User $user */
    $user = User::factory()->create([
        'role' => UserRole::from($roleName),
        'status' => UserStatus::Active,
    ]);

    actingAs($user);

    $response = get(route('admin.settings'));

    $response->assertStatus($expectedStatus);

    if ($expectedStatus === 403) {
        $response->assertSee(__('ui.errors.403_title'));
    } else {
        $response->assertSee(__('admin.settings.title'));
    }
})->with([
    'regular user role' => ['user', 403],
    'inspector role' => ['inspector', 403],
    'admin role' => ['admin', 200],
]);
