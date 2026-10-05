<?php

declare(strict_types=1);

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class RegisterUser
{
    /**
     * @param  array{name: string, email: string, phone: string, password: string, role?: string|UserRole, accepted_terms?: bool}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $inputRole = $data['role'] ?? 'user';
            $roleString = $inputRole instanceof UserRole ? $inputRole->value : (string) $inputRole;

            // AUTH-T05 / AUTH-E2: Admin role from registration is ignored and forced to user
            if ($roleString === UserRole::Admin->value) {
                $role = UserRole::User;
            } elseif ($roleString === UserRole::Inspector->value) {
                $role = UserRole::Inspector;
            } else {
                $role = UserRole::User;
            }

            // AUTH-T04: Inspector registers with pending status, user with active status
            $status = ($role === UserRole::Inspector) ? UserStatus::Pending : UserStatus::Active;

            $acceptedTerms = ! empty($data['accepted_terms']);

            $user = User::create([
                'name' => trim((string) $data['name']),
                'email' => strtolower(trim((string) $data['email'])),
                'phone' => trim((string) $data['phone']),
                'password' => Hash::make((string) $data['password']),
                'role' => $role,
                'status' => $status,
                'accepted_terms_at' => $acceptedTerms ? now() : null,
            ]);

            event(new Registered($user));

            return $user;
        });
    }
}
