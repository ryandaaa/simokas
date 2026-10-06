<?php

declare(strict_types=1);

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Livewire\Register;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('AUTH-T01 registers user with valid data and redirects to dashboard', function () {
    Livewire::test(Register::class)
        ->set('name', 'Budi Santoso')
        ->set('email', 'budi@example.com')
        ->set('phone', '081234567890')
        ->set('password', 'Rahasia123!')
        ->set('password_confirmation', 'Rahasia123!')
        ->set('account_type', 'user')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertRedirect(route('dashboard'));

    expect(Auth::check())->toBeTrue();
    /** @var User|null $user */
    $user = Auth::user();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Budi Santoso')
        ->and($user->email)->toBe('budi@example.com')
        ->and($user->phone)->toBe('081234567890')
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->accepted_terms_at)->not->toBeNull();
});

it('AUTH-T02 rejects invalid email and phone with field errors', function (string $email, string $phone, array $expectedErrors) {
    Livewire::test(Register::class)
        ->set('name', 'Pengguna Uji')
        ->set('email', $email)
        ->set('phone', $phone)
        ->set('password', 'ValidPass123!')
        ->set('password_confirmation', 'ValidPass123!')
        ->set('account_type', 'user')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertHasErrors($expectedErrors);
})->with([
    'invalid email format' => ['not-an-email', '081234567890', ['email']],
    'empty email' => ['', '081234567890', ['email']],
    'invalid phone format (too short)' => ['valid@example.com', '081234', ['phone']],
    'invalid phone format (not indonesian)' => ['valid@example.com', '+14155552671', ['phone']],
    'invalid phone format (contains letters)' => ['valid@example.com', '0812abc3456', ['phone']],
    'both invalid' => ['invalid-email', '0712345678', ['email', 'phone']],
]);

it('AUTH-T03 rejects weak passwords', function (string $password, string $confirmation) {
    Livewire::test(Register::class)
        ->set('name', 'Pengguna Uji')
        ->set('email', 'test@example.com')
        ->set('phone', '081234567890')
        ->set('password', $password)
        ->set('password_confirmation', $confirmation)
        ->set('account_type', 'user')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertHasErrors(['password']);
})->with([
    'too short (less than 8)' => ['Ab1!', 'Ab1!'],
    'only letters' => ['PasswordOnly', 'PasswordOnly'],
    'only numbers' => ['1234567890', '1234567890'],
    'unconfirmed' => ['Password123!', 'DifferentPassword123!'],
]);

it('AUTH-T04 registers inspector with pending status', function () {
    Livewire::test(Register::class)
        ->set('name', 'Doni Teknisi')
        ->set('email', 'doni@example.com')
        ->set('phone', '081298765432')
        ->set('password', 'Inspektor123!')
        ->set('password_confirmation', 'Inspektor123!')
        ->set('account_type', 'inspector')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertRedirect(route('dashboard'));

    /** @var User|null $user */
    $user = User::query()->where('email', 'doni@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Inspector)
        ->and($user->status)->toBe(UserStatus::Pending);
});

it('AUTH-T05 ignores role admin from form manipulation and registers as user', function () {
    Livewire::test(Register::class)
        ->set('name', 'Calon Hacker')
        ->set('email', 'hacker@example.com')
        ->set('phone', '081211112222')
        ->set('password', 'HackerPass123!')
        ->set('password_confirmation', 'HackerPass123!')
        ->set('account_type', 'admin')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertRedirect(route('dashboard'));

    /** @var User|null $user */
    $user = User::query()->where('email', 'hacker@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::User)
        ->and($user->status)->toBe(UserStatus::Active);
});

it('AUTH-T06 rejects duplicate email', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    Livewire::test(Register::class)
        ->set('name', 'Pengguna Baru')
        ->set('email', 'duplicate@example.com')
        ->set('phone', '081234567890')
        ->set('password', 'Rahasia123!')
        ->set('password_confirmation', 'Rahasia123!')
        ->set('account_type', 'user')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertHasErrors(['email']);
});

it('AUTH-E1 displays friendly duplicate email message with login and reset links', function () {
    User::factory()->create(['email' => 'registered@example.com']);

    $component = Livewire::test(Register::class)
        ->set('name', 'Pengguna Baru')
        ->set('email', 'registered@example.com')
        ->set('phone', '081234567890')
        ->set('password', 'Rahasia123!')
        ->set('password_confirmation', 'Rahasia123!')
        ->set('account_type', 'user')
        ->set('accepted_terms', true)
        ->call('register');

    $component->assertHasErrors(['email'])
        ->assertSee(__('identity.register.duplicate_email'))
        ->assertSee(__('identity.register.duplicate_email_login'))
        ->assertSee(__('identity.register.duplicate_email_reset'));
});

it('AUTH-T15 rejects registration without valid terms consent', function (mixed $value) {
    $component = Livewire::test(Register::class)
        ->set('name', 'Budi Santoso')
        ->set('email', 'budi@example.com')
        ->set('phone', '081234567890')
        ->set('password', 'Rahasia123!')
        ->set('password_confirmation', 'Rahasia123!')
        ->set('account_type', 'user')
        ->set('accepted_terms', $value)
        ->call('register');

    $component->assertHasErrors(['accepted_terms'])
        ->assertSee(__('identity.register.terms_required'));

    expect(User::query()->where('email', 'budi@example.com')->exists())->toBeFalse();
})->with([
    'unchecked (false)' => [false],
    'empty string' => [''],
    'zero string' => ['0'],
    'weird string' => ['maybe'],
    'null' => [null],
]);

it('AUTH-T16 records accepted_terms_at at registration time when consent is given', function ($testCase) {
    $frozen = CarbonImmutable::parse('2026-10-05 10:00:00', 'UTC');
    $testCase->travelTo($frozen);

    Livewire::test(Register::class)
        ->set('name', 'Siti Setuju')
        ->set('email', 'siti@example.com')
        ->set('phone', '081234567891')
        ->set('password', 'Rahasia123!')
        ->set('password_confirmation', 'Rahasia123!')
        ->set('account_type', 'user')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertRedirect(route('dashboard'));

    /** @var User|null $user */
    $user = User::query()->where('email', 'siti@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->accepted_terms_at)->not->toBeNull()
        ->and($user->accepted_terms_at->equalTo($frozen))->toBeTrue();
});
