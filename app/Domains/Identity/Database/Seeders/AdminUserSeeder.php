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
        $email = config('auth.admin.email');
        $password = config('auth.admin.password');

        if (empty($email) || ! is_string($email)) {
            throw new RuntimeException(
                'ADMIN_EMAIL belum diatur di file .env atau konfigurasi auth.admin.email. Silakan atur alamat email admin yang valid sebelum menjalankan seeder.'
            );
        }

        $email = trim($email);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(
                "Format ADMIN_EMAIL '{$email}' tidak valid. Silakan gunakan format email yang benar di file .env."
            );
        }

        if (empty($password) || ! is_string($password) || $password === 'GANTI-DENGAN-PASSWORD-KUAT-MIN-12-KARAKTER') {
            throw new RuntimeException(
                'ADMIN_PASSWORD belum diatur atau masih menggunakan nilai placeholder di file .env. Silakan atur variabel ADMIN_PASSWORD dengan kata sandi yang kuat (minimal 12 karakter) sebelum menjalankan seeder.'
            );
        }

        if (strlen($password) < 12) {
            throw new RuntimeException(
                'ADMIN_PASSWORD terlalu pendek. Kata sandi akun admin wajib minimal 12 karakter demi keamanan.'
            );
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator SIMOKAS',
                'phone' => '081200000000',
                'password' => Hash::make($password),
                'role' => UserRole::Admin,
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
                'accepted_terms_at' => now(),
            ]
        );
    }
}
