<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Livewire\Login;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('logs in active user successfully and redirects to dashboard', function () {
    $user = User::factory()->active()->create([
        'email' => 'active@example.com',
        'password' => 'Password123!',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'active@example.com')
        ->set('password', 'Password123!')
        ->call('login')
        ->assertRedirect(route('dashboard'));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);
});

it('logs in pending inspector successfully', function () {
    $user = User::factory()->inspector()->pending()->create([
        'email' => 'inspector@example.com',
        'password' => 'Password123!',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'inspector@example.com')
        ->set('password', 'Password123!')
        ->call('login')
        ->assertRedirect(route('dashboard'));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);
});

it('AUTH-T07 does not distinguish between wrong email and wrong password on login failure', function () {
    User::factory()->create([
        'email' => 'existing@example.com',
        'password' => 'CorrectPassword123!',
    ]);

    // Scenario 1: Email exists, wrong password
    $wrongPasswordComponent = Livewire::test(Login::class)
        ->set('email', 'existing@example.com')
        ->set('password', 'WrongPassword123!')
        ->call('login')
        ->assertHasErrors(['email']);

    $wrongPasswordMessage = $wrongPasswordComponent->errors()->first('email');

    RateLimiter::clear('login:existing@example.com|127.0.0.1');

    // Scenario 2: Email does not exist
    $wrongEmailComponent = Livewire::test(Login::class)
        ->set('email', 'nonexistent@example.com')
        ->set('password', 'AnyPassword123!')
        ->call('login')
        ->assertHasErrors(['email']);

    $wrongEmailMessage = $wrongEmailComponent->errors()->first('email');

    expect($wrongPasswordMessage)->toBe(__('identity.login.failed'))
        ->and($wrongEmailMessage)->toBe(__('identity.login.failed'))
        ->and($wrongPasswordMessage)->toBe($wrongEmailMessage);
});

it('AUTH-T08 rate limits repeated login attempts', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'Password123!',
    ]);

    Carbon::setTestNow(now());

    // 5 attempts allowed
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'user@example.com')
            ->set('password', 'WrongPassword123!')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    // 6th attempt should be blocked with 429
    Livewire::test(Login::class)
        ->set('email', 'user@example.com')
        ->set('password', 'WrongPassword123!')
        ->call('login')
        ->assertStatus(429);

    // Freeze time and travel 61 seconds into future
    Carbon::setTestNow(now()->addSeconds(61));

    // After throttle period, user can attempt login again
    Livewire::test(Login::class)
        ->set('email', 'user@example.com')
        ->set('password', 'Password123!')
        ->call('login')
        ->assertRedirect(route('dashboard'));

    expect(Auth::check())->toBeTrue();

    Carbon::setTestNow();
});

it('AUTH-T09 prevents blocked users from logging in', function () {
    User::factory()->blocked()->create([
        'email' => 'blocked@example.com',
        'password' => 'Password123!',
    ]);

    Livewire::test(Login::class)
        ->set('email', 'blocked@example.com')
        ->set('password', 'Password123!')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(Auth::check())->toBeFalse();
});

it('AUTH-E4 rejects active session if user becomes blocked and redirects to blocked page', function () {
    $user = User::factory()->active()->create();

    // Authenticated request works when user is active
    actingAs($user);
    get(route('dashboard'))
        ->assertOk();

    // Admin blocks the user while logged in
    $user->update(['status' => UserStatus::Blocked]);

    // Next request is rejected and redirected to blocked page
    /** @var User $freshUser */
    $freshUser = $user->fresh();
    actingAs($freshUser);
    $response = get(route('dashboard'));

    $response->assertRedirect(route('identity.blocked'));
    expect(Auth::check())->toBeFalse();
});

it('logs out authenticated user and invalidates session', function () {
    $user = User::factory()->active()->create();

    actingAs($user);
    post(route('identity.logout'))
        ->assertRedirect(route('login'));

    expect(Auth::check())->toBeFalse();
});
