<?php

declare(strict_types=1);

use App\Domains\Identity\Database\Seeders\AdminUserSeeder;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Livewire\Register;
use App\Domains\Identity\Models\User;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('AUTH-T14 creates admin user via ReferenceSeeder and prevents admin creation from public registration', function () {
    // Part 1: ReferenceSeeder creates admin user from configuration/environment variables
    $adminEmail = 'testadmin@simokas.com';
    $adminPassword = 'CustomAdminPass2026!';

    config([
        'auth.admin.email' => $adminEmail,
        'auth.admin.password' => $adminPassword,
    ]);

    (new ReferenceSeeder)->run();

    /** @var User|null $admin */
    $admin = User::query()->where('email', $adminEmail)->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->status)->toBe(UserStatus::Active)
        ->and(Hash::check($adminPassword, $admin->password))->toBeTrue();

    // Part 2: Admin CANNOT be created from public registration route
    Livewire::test(Register::class)
        ->set('name', 'Pendaftar Palsu')
        ->set('email', 'attemptadmin@simokas.com')
        ->set('phone', '081299998888')
        ->set('password', 'AttemptPass123!')
        ->set('password_confirmation', 'AttemptPass123!')
        ->set('account_type', 'admin')
        ->set('accepted_terms', true)
        ->call('register');

    /** @var User|null $attemptUser */
    $attemptUser = User::query()->where('email', 'attemptadmin@simokas.com')->first();
    expect($attemptUser)->not->toBeNull()
        ->and($attemptUser->role)->not->toBe(UserRole::Admin)
        ->and($attemptUser->role)->toBe(UserRole::User);
});

it('throws an exception if ADMIN_PASSWORD is empty in production', function () {
    $originalEnv = app()->environment();
    app()->detectEnvironment(fn () => 'production');
    config(['auth.admin.password' => null]);

    try {
        expect(fn () => (new AdminUserSeeder)->run())
            ->toThrow(RuntimeException::class, 'ADMIN_PASSWORD environment variable must be set in production.');
    } finally {
        app()->detectEnvironment(fn () => $originalEnv);
    }
});
