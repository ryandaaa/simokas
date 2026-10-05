<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Actions\RegisterUser;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class RegisterUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_standard_user_with_active_status(): void
    {
        Event::fake([Registered::class]);

        $action = new RegisterUser;

        $user = $action->handle([
            'name' => 'Budi Santoso',
            'email' => 'Budi@Example.com',
            'phone' => '081234567890',
            'password' => 'Rahasia123',
            'role' => 'user',
            'accepted_terms' => true,
        ]);

        $this->assertNotEmpty($user->id);
        $this->assertSame('Budi Santoso', $user->name);
        $this->assertSame('budi@example.com', $user->email);
        $this->assertSame('081234567890', $user->phone);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue(Hash::check('Rahasia123', $user->password));
        $this->assertNotNull($user->accepted_terms_at);

        Event::assertDispatched(Registered::class, fn (Registered $e) => $e->user instanceof User && $e->user->id === $user->id);
    }

    public function test_it_registers_an_inspector_with_pending_status(): void
    {
        Event::fake([Registered::class]);

        $action = new RegisterUser;

        $user = $action->handle([
            'name' => 'Doni Teknisi',
            'email' => 'doni@example.com',
            'phone' => '081298765432',
            'password' => 'Inspektor123',
            'role' => 'inspector',
            'accepted_terms' => true,
        ]);

        $this->assertSame(UserRole::Inspector, $user->role);
        $this->assertSame(UserStatus::Pending, $user->status);
    }

    public function test_it_ignores_admin_role_and_defaults_to_user(): void
    {
        Event::fake([Registered::class]);

        $action = new RegisterUser;

        $user = $action->handle([
            'name' => 'Hacker Admin',
            'email' => 'hacker@example.com',
            'phone' => '081211112222',
            'password' => 'Password123',
            'role' => 'admin',
            'accepted_terms' => true,
        ]);

        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_it_records_accepted_terms_at_properly(): void
    {
        $action = new RegisterUser;

        $user = $action->handle([
            'name' => 'Agus Setuju',
            'email' => 'agus@example.com',
            'phone' => '081233334444',
            'password' => 'Password123',
            'role' => 'user',
            'accepted_terms' => true,
        ]);

        $this->assertNotNull($user->accepted_terms_at);
    }
}
