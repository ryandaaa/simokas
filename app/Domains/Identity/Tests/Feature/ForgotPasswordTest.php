<?php

declare(strict_types=1);

use App\Domains\Identity\Livewire\ForgotPassword;
use App\Domains\Identity\Livewire\ResetPassword;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('returns identical success response whether email exists or not', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'registered@example.com']);

    // Scenario 1: Registered user
    Livewire::test(ForgotPassword::class)
        ->set('email', 'registered@example.com')
        ->call('sendResetLink')
        ->assertSee(__('identity.forgot_password.success_notice'));

    Notification::assertSentTo($user, ResetPasswordNotification::class);

    // Scenario 2: Unregistered email
    Livewire::test(ForgotPassword::class)
        ->set('email', 'unregistered@example.com')
        ->call('sendResetLink')
        ->assertSee(__('identity.forgot_password.success_notice'));
});

it('AUTH-T08 rate limits forgot password requests', function () {
    Notification::fake();

    User::factory()->create(['email' => 'user@example.com']);

    Carbon::setTestNow(now());

    // 3 attempts allowed
    for ($i = 0; $i < 3; $i++) {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'user@example.com')
            ->call('sendResetLink')
            ->assertSee(__('identity.forgot_password.success_notice'));
    }

    // 4th attempt should be throttled (429)
    Livewire::test(ForgotPassword::class)
        ->set('email', 'user@example.com')
        ->call('sendResetLink')
        ->assertStatus(429);

    // Travel 61 seconds into future
    Carbon::setTestNow(now()->addSeconds(61));

    Livewire::test(ForgotPassword::class)
        ->set('email', 'user@example.com')
        ->call('sendResetLink')
        ->assertSee(__('identity.forgot_password.success_notice'));

    Carbon::setTestNow();
});

it('AUTH-T10 resets password successfully and rejects expired or invalid token', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => Hash::make('OldPassword123!'),
    ]);

    $validToken = Password::broker()->createToken($user);

    // Step 1: Successful password reset
    Livewire::test(ResetPassword::class, ['token' => $validToken])
        ->set('email', 'user@example.com')
        ->set('password', 'NewSecurePass123!')
        ->set('password_confirmation', 'NewSecurePass123!')
        ->call('resetPassword')
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', __('identity.reset_password.success'));

    $user->refresh();
    expect(Hash::check('NewSecurePass123!', $user->password))->toBeTrue();

    // Step 2: Attempting with already used or invalid token fails (AUTH-E6)
    Livewire::test(ResetPassword::class, ['token' => $validToken])
        ->set('email', 'user@example.com')
        ->set('password', 'AnotherPassword123!')
        ->set('password_confirmation', 'AnotherPassword123!')
        ->call('resetPassword')
        ->assertHasErrors(['email'])
        ->assertSee(__('identity.reset_password.invalid_token'))
        ->assertSee(__('identity.reset_password.request_new_link'));

    expect(Hash::check('NewSecurePass123!', $user->refresh()->password))->toBeTrue();
});
