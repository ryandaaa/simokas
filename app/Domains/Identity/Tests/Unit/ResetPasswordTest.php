<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Actions\ResetPassword;
use App\Domains\Identity\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resets_password_with_valid_token(): void
    {
        Event::fake([PasswordReset::class]);

        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $token = Password::broker()->createToken($user);

        $action = new ResetPassword;
        $status = $action->handle('user@example.com', 'NewPassword123!', $token);

        $this->assertSame(Password::PASSWORD_RESET, $status);
        $this->assertTrue(Hash::check('NewPassword123!', $user->refresh()->password));

        Event::assertDispatched(PasswordReset::class);
    }

    public function test_it_fails_to_reset_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $action = new ResetPassword;
        $status = $action->handle('user@example.com', 'NewPassword123!', 'invalid-token');

        $this->assertSame(Password::INVALID_TOKEN, $status);
        $this->assertTrue(Hash::check('OldPassword123!', $user->refresh()->password));
    }
}
