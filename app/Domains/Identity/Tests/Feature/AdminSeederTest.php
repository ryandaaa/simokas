<?php

declare(strict_types=1);

use App\Domains\Identity\Database\Seeders\AdminUserSeeder;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Livewire\Register;
use App\Domains\Identity\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('AUTH-T14 creates admin user via ReferenceSeeder and prevents admin creation from public registration', function () {
    // Part 1: ReferenceSeeder creates admin user from configured environment credentials
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

it('executes DatabaseSeeder successfully when admin config is provided', function () {
    config([
        'auth.admin.email' => 'seederadmin@simokas.com',
        'auth.admin.password' => 'SeederAdminPassword2026!',
    ]);

    (new DatabaseSeeder)->run();

    /** @var User|null $admin */
    $admin = User::query()->where('email', 'seederadmin@simokas.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->email)->toBe('seederadmin@simokas.com')
        ->and($admin->role)->toBe(UserRole::Admin);
});

it('throws an exception if ADMIN_PASSWORD is empty in any environment', function () {
    config([
        'auth.admin.email' => 'admin@simokas.com',
        'auth.admin.password' => null,
    ]);

    expect(fn () => (new AdminUserSeeder)->run())
        ->toThrow(RuntimeException::class, 'ADMIN_PASSWORD belum diatur atau masih menggunakan nilai placeholder');
});

it('throws an exception if ADMIN_PASSWORD uses placeholder value', function () {
    config([
        'auth.admin.email' => 'admin@simokas.com',
        'auth.admin.password' => 'GANTI-DENGAN-PASSWORD-KUAT-MIN-12-KARAKTER',
    ]);

    expect(fn () => (new AdminUserSeeder)->run())
        ->toThrow(RuntimeException::class, 'ADMIN_PASSWORD belum diatur atau masih menggunakan nilai placeholder');
});

it('throws an exception if ADMIN_PASSWORD is shorter than 12 characters', function () {
    config([
        'auth.admin.email' => 'admin@simokas.com',
        'auth.admin.password' => 'ShortPass1!',
    ]);

    expect(fn () => (new AdminUserSeeder)->run())
        ->toThrow(RuntimeException::class, 'ADMIN_PASSWORD terlalu pendek. Kata sandi akun admin wajib minimal 12 karakter demi keamanan.');
});

it('throws an exception if ADMIN_EMAIL is empty', function () {
    config([
        'auth.admin.email' => null,
        'auth.admin.password' => 'StrongAdminPass123!',
    ]);

    expect(fn () => (new AdminUserSeeder)->run())
        ->toThrow(RuntimeException::class, 'ADMIN_EMAIL belum diatur di file .env atau konfigurasi auth.admin.email.');
});

it('throws an exception if ADMIN_EMAIL is not a valid email', function () {
    config([
        'auth.admin.email' => 'not-a-valid-email',
        'auth.admin.password' => 'StrongAdminPass123!',
    ]);

    expect(fn () => (new AdminUserSeeder)->run())
        ->toThrow(RuntimeException::class, "Format ADMIN_EMAIL 'not-a-valid-email' tidak valid.");
});
