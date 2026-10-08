<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Livewire\Login;
use App\Domains\Identity\Models\User;
use App\Domains\Inspector\Models\InspectorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('redirects unauthenticated users from dashboard to login', function () {
    get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('AUTH-T11 shows role-specific content at /dashboard for each role', function (string $roleName, string $expectedContentKey) {
    /** @var User $user */
    $user = User::factory()->create([
        'role' => UserRole::from($roleName),
        'status' => UserStatus::Active,
    ]);

    if ($user->role === UserRole::Inspector) {
        InspectorProfile::factory()->approved()->create([
            'user_id' => $user->id,
        ]);
    }

    actingAs($user);

    get(route('dashboard'))
        ->assertOk()
        ->assertSee(__($expectedContentKey));
})->with([
    'user role' => ['user', 'dashboard.buyer.title'],
    'inspector role' => ['inspector', 'dashboard.inspector.title'],
    'admin role' => ['admin', 'dashboard.admin.title'],
]);

it('AUTH-T11 redirects to the unified dashboard after login', function (string $roleName) {
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
        ->assertRedirect(route('dashboard'));

    expect(Auth::check())->toBeTrue();
})->with([
    'user login redirect' => ['user'],
    'inspector login redirect' => ['inspector'],
    'admin login redirect' => ['admin'],
]);
