<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Livewire\AdminDashboard;
use App\Domains\Identity\Livewire\InspectorDashboard;
use App\Domains\Identity\Livewire\Login;
use App\Domains\Identity\Livewire\UserDashboard;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects unauthenticated users from dashboards to login', function (string $route) {
    get(route($route))
        ->assertRedirect(route('login'));
})->with([
    'main dashboard' => ['dashboard'],
    'user dashboard' => ['dashboard.user'],
    'inspector dashboard' => ['dashboard.inspector'],
    'admin dashboard' => ['dashboard.admin'],
]);

it('AUTH-T11 shows role-specific content at /dashboard for each role', function (string $roleName, string $targetRoute, string $expectedContentKey) {
    /** @var User $user */
    $user = User::factory()->create([
        'role' => UserRole::from($roleName),
        'status' => UserStatus::Active,
    ]);

    actingAs($user);

    // Dashboard domain now handles role-based rendering at /dashboard
    // instead of redirecting to separate role-specific routes
    get(route('dashboard'))
        ->assertOk()
        ->assertSee(__($expectedContentKey));
})->with([
    'user role' => ['user', 'dashboard.user', 'dashboard.buyer.title'],
    'inspector role' => ['inspector', 'dashboard.inspector', 'dashboard.inspector.title'],
    'admin role' => ['admin', 'dashboard.admin', 'dashboard.admin.title'],
]);

it('AUTH-T11 allows authenticated users to view their respective role dashboard', function (string $roleName, string $route, string $expectedTitleKey) {
    /** @var User $user */
    $user = User::factory()->create([
        'name' => 'Pengguna Uji '.$roleName,
        'role' => UserRole::from($roleName),
        'status' => UserStatus::Active,
    ]);

    actingAs($user);

    get(route($route))
        ->assertOk()
        ->assertSee('Pengguna Uji '.$roleName)
        ->assertSee(__($expectedTitleKey))
        ->assertSee(__('identity.dashboard.notice'));
})->with([
    'user dashboard' => ['user', 'dashboard.user', 'identity.dashboard.user_title'],
    'inspector dashboard' => ['inspector', 'dashboard.inspector', 'identity.dashboard.inspector_title'],
    'admin dashboard' => ['admin', 'dashboard.admin', 'identity.dashboard.admin_title'],
]);

it('AUTH-T11 shows verification pending alert for pending inspector on inspector dashboard', function () {
    /** @var User $pendingInspector */
    $pendingInspector = User::factory()->inspector()->pending()->create([
        'name' => 'Inspektor Pending',
    ]);

    actingAs($pendingInspector);

    get(route('dashboard.inspector'))
        ->assertOk()
        ->assertSee('Inspektor Pending')
        ->assertSee(__('identity.dashboard.inspector_pending_title'))
        ->assertSee(__('identity.dashboard.inspector_pending_alert'));
});

it('AUTH-T11 does not show verification pending alert for active inspector on inspector dashboard', function () {
    /** @var User $activeInspector */
    $activeInspector = User::factory()->inspector()->active()->create([
        'name' => 'Inspektor Aktif',
    ]);

    actingAs($activeInspector);

    get(route('dashboard.inspector'))
        ->assertOk()
        ->assertSee('Inspektor Aktif')
        ->assertDontSee(__('identity.dashboard.inspector_pending_alert'));
});

it('AUTH-T11 redirects to role-specific dashboard after login', function (string $roleName, string $targetRoute) {
    User::factory()->create([
        'email' => "login_{$roleName}@example.com",
        'password' => 'Password123!',
        'role' => UserRole::from($roleName),
        'status' => UserStatus::Active,
    ]);

    Livewire::test(Login::class)
        ->set('email', "login_{$roleName}@example.com")
        ->set('password', 'Password123!')
        ->call('login')
        ->assertRedirect(route($targetRoute));

    expect(Auth::check())->toBeTrue();
})->with([
    'user login redirect' => ['user', 'dashboard.user'],
    'inspector login redirect' => ['inspector', 'dashboard.inspector'],
    'admin login redirect' => ['admin', 'dashboard.admin'],
]);

it('allows user to logout from role dashboards', function (string $componentClass) {
    $user = User::factory()->active()->create();

    actingAs($user);

    Livewire::test($componentClass)
        ->call('logout')
        ->assertRedirect(route('login'));

    expect(Auth::check())->toBeFalse();
})->with([
    'user dashboard logout' => [UserDashboard::class],
    'inspector dashboard logout' => [InspectorDashboard::class],
    'admin dashboard logout' => [AdminDashboard::class],
]);
