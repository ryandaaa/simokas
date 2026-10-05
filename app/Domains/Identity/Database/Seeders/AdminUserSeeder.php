<?php

declare(strict_types=1);

namespace App\Domains\Identity\Database\Seeders;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('auth.admin.email', 'admin@simokas.com');
        $password = config('auth.admin.password');

        if (empty($password) && app()->environment('production')) {
            throw new RuntimeException('ADMIN_PASSWORD environment variable must be set in production.');
        }

        $passwordString = ! empty($password) ? (string) $password : 'AdminSimokas2026!';

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator SIMOKAS',
                'phone' => '081200000000',
                'password' => Hash::make($passwordString),
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'accepted_terms_at' => now(),
            ]
        );
    }
}
