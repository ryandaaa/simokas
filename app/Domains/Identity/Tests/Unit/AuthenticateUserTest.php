<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Actions\AuthenticateUser;
use App\Domains\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AuthenticateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_authenticates_an_active_user(): void
    {
        $user = User::factory()->active()->create([
            'email' => 'active@example.com',
            'password' => 'Password123!',
        ]);

        $action = new AuthenticateUser;
        $result = $action->handle('active@example.com', 'Password123!');

        $this->assertSame($user->id, $result->id);
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    public function test_it_authenticates_a_pending_inspector(): void
    {
        $user = User::factory()->inspector()->pending()->create([
            'email' => 'inspector@example.com',
            'password' => 'Password123!',
        ]);

        $action = new AuthenticateUser;
        $result = $action->handle('inspector@example.com', 'Password123!');

        $this->assertSame($user->id, $result->id);
        $this->assertTrue(Auth::check());
    }

    public function test_it_throws_validation_exception_for_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'Password123!',
        ]);

        $this->expectException(ValidationException::class);

        $action = new AuthenticateUser;
        $action->handle('user@example.com', 'WrongPassword123!');
    }

    public function test_it_throws_validation_exception_for_non_existent_email(): void
    {
        $this->expectException(ValidationException::class);

        $action = new AuthenticateUser;
        $action->handle('nonexistent@example.com', 'Password123!');
    }

    public function test_it_throws_validation_exception_for_blocked_user(): void
    {
        User::factory()->blocked()->create([
            'email' => 'blocked@example.com',
            'password' => 'Password123!',
        ]);

        $this->expectException(ValidationException::class);

        $action = new AuthenticateUser;
        $action->handle('blocked@example.com', 'Password123!');
    }
}
