<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Support\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Route::middleware(['web', 'role:user'])->get('/test-role/user-only', function () {
        return response('Halaman Pengguna', 200);
    })->name('test-role.user');

    Route::middleware(['web', 'role:inspector'])->get('/test-role/inspector-only', function () {
        return response('Halaman Inspektor', 200);
    })->name('test-role.inspector');

    Route::middleware(['web', 'role:admin'])->get('/test-role/admin-only', function () {
        return response('Halaman Admin', 200);
    })->name('test-role.admin');

    Route::middleware(['web', 'role:inspector,user'])->get('/test-role/multi-role', function () {
        return response('Halaman Multi Role', 200);
    })->name('test-role.multi');

    Route::middleware(['web', 'role:unknown_role'])->get('/test-role/unknown', function () {
        return response('Halaman Unknown Role', 200);
    })->name('test-role.unknown');
});

it('AUTH-T12 prevents cross-role access and enforces 403 on role-protected routes', function (string $userRole, string $routeUrl, int $expectedStatus) {
    /** @var User $user */
    $user = User::factory()->create([
        'role' => UserRole::from($userRole),
        'status' => UserStatus::Active,
    ]);

    actingAs($user);

    $response = get($routeUrl);

    $response->assertStatus($expectedStatus);

    if ($expectedStatus === 403) {
        $response->assertSee(__('ui.errors.403_title'));
    }
})->with([
    // User role accessing routes
    'user accessing user route' => ['user', '/test-role/user-only', 200],
    'user accessing inspector route' => ['user', '/test-role/inspector-only', 403],
    'user accessing admin route' => ['user', '/test-role/admin-only', 403],

    // Inspector role accessing routes
    'inspector accessing user route' => ['inspector', '/test-role/user-only', 403],
    'inspector accessing inspector route' => ['inspector', '/test-role/inspector-only', 200],
    'inspector accessing admin route' => ['inspector', '/test-role/admin-only', 403],

    // Admin role accessing routes
    'admin accessing user route' => ['admin', '/test-role/user-only', 403],
    'admin accessing inspector route' => ['admin', '/test-role/inspector-only', 403],
    'admin accessing admin route' => ['admin', '/test-role/admin-only', 200],

    // Multi-role route (inspector, user)
    'user accessing multi-role route' => ['user', '/test-role/multi-role', 200],
    'inspector accessing multi-role route' => ['inspector', '/test-role/multi-role', 200],
    'admin accessing multi-role route' => ['admin', '/test-role/multi-role', 403],
]);

it('AUTH-T12 returns 403 JSON response with NOT_ALLOWED code when forbidden request expects json', function () {
    $user = User::factory()->active()->create([
        'role' => UserRole::User,
    ]);

    actingAs($user);

    $response = getJson('/test-role/admin-only');

    $response->assertStatus(403)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => DomainException::CODE_NOT_ALLOWED,
                'message' => __('identity.errors.forbidden'),
            ],
        ]);
});

it('AUTH-E8 forbids inspector from accessing buyer or seller routes with 403', function () {
    $inspector = User::factory()->inspector()->active()->create();

    actingAs($inspector);

    // Inspector accessing buyer/seller page protected by role:user
    $response = get('/test-role/user-only');
    $response->assertStatus(403)
        ->assertSee(__('ui.errors.403_title'));

    // Inspector accessing buyer/seller json API
    $jsonResponse = getJson('/test-role/user-only');
    $jsonResponse->assertStatus(403)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => DomainException::CODE_NOT_ALLOWED,
                'message' => __('identity.errors.forbidden'),
            ],
        ]);
});

it('redirects unauthenticated web user on role-protected route to login', function () {
    $response = get('/test-role/user-only');

    $response->assertRedirect(route('login'));
});

it('returns 401 JSON for unauthenticated json request on role-protected route', function () {
    $response = getJson('/test-role/user-only');

    $response->assertStatus(401)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => DomainException::CODE_UNAUTHENTICATED,
                'message' => __('identity.errors.unauthenticated'),
            ],
        ]);
});

it('rejects access to unknown role route with 403', function () {
    $user = User::factory()->active()->create([
        'role' => UserRole::User,
    ]);

    actingAs($user);

    $response = get('/test-role/unknown');
    $response->assertStatus(403);
});

it('rejects blocked user on role-protected route', function () {
    $blockedUser = User::factory()->blocked()->create([
        'role' => UserRole::User,
    ]);

    actingAs($blockedUser);

    // Web request redirects to identity.blocked
    $webResponse = get('/test-role/user-only');
    $webResponse->assertRedirect(route('identity.blocked'));

    // JSON request returns 403 JSON
    actingAs($blockedUser);
    $jsonResponse = getJson('/test-role/user-only');
    $jsonResponse->assertStatus(403)
        ->assertJson([
            'data' => null,
            'error' => [
                'code' => DomainException::CODE_NOT_ALLOWED,
                'message' => __('identity.login.blocked_message'),
            ],
        ]);
});
