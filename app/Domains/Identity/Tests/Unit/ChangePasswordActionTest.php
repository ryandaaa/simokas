<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Actions\ChangePassword;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ChangePasswordActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_changes_password_successfully_with_correct_current_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('PasswordLama123!'),
        ]);

        Auth::login($user);

        $action = new ChangePassword;
        $updatedUser = $action->handle($user, 'PasswordLama123!', 'PasswordBaru123!');

        $this->assertTrue(Hash::check('PasswordBaru123!', $updatedUser->password));

        $user->refresh();
        $this->assertTrue(Hash::check('PasswordBaru123!', $user->password));
    }

    public function test_it_throws_validation_exception_when_current_password_is_wrong(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('PasswordBenar123!'),
        ]);

        Auth::login($user);

        $action = new ChangePassword;

        try {
            $action->handle($user, 'PasswordSalah123!', 'PasswordBaru123!');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('current_password', $e->errors());
        }

        $user->refresh();
        $this->assertTrue(Hash::check('PasswordBenar123!', $user->password));
    }

    public function test_it_throws_authorization_exception_when_attempting_to_change_another_user_password(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create([
            'password' => Hash::make('PasswordSatu123!'),
        ]);
        /** @var User $user2 */
        $user2 = User::factory()->create([
            'password' => Hash::make('PasswordDua123!'),
        ]);

        Auth::login($user1);

        $this->expectException(AuthorizationException::class);

        $action = new ChangePassword;
        $action->handle($user2, 'PasswordDua123!', 'PasswordBaru123!');
    }

    public function test_it_preserves_user_authentication_in_the_current_session(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('PasswordLama123!'),
        ]);

        Auth::login($user);
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());

        $action = new ChangePassword;
        $action->handle($user, 'PasswordLama123!', 'PasswordBaru123!');

        // Session remains authenticated on current device (AUTH-E10)
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    public function test_it_strictly_ignores_extra_input_or_model_tampering(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('PasswordLama123!'),
            'role' => UserRole::User,
            'status' => UserStatus::Active,
            'email' => 'user@example.com',
        ]);

        Auth::login($user);

        $action = new ChangePassword;
        $action->handle($user, 'PasswordLama123!', 'PasswordBaru123!', [
            'role' => 'admin',
            'status' => 'blocked',
            'email' => 'other@example.com',
        ]);

        $user->refresh();
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertSame('user@example.com', $user->email);
    }
}
