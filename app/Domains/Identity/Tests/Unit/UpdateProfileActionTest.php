<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Actions\UpdateProfile;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class UpdateProfileActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_user_name_and_phone_successfully_in_a_database_transaction(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'phone' => '081234567890',
        ]);

        Auth::login($user);

        $action = new UpdateProfile;
        $updatedUser = $action->handle($user, [
            'name' => 'Nama Baru',
            'phone' => '089876543210',
        ]);

        $this->assertSame('Nama Baru', $updatedUser->name);
        $this->assertSame('089876543210', $updatedUser->phone);

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('089876543210', $user->phone);
    }

    public function test_it_trims_whitespace_from_name_and_phone(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        Auth::login($user);

        $action = new UpdateProfile;
        $updatedUser = $action->handle($user, [
            'name' => '   Nama Spasi   ',
            'phone' => '   081234567890   ',
        ]);

        $this->assertSame('Nama Spasi', $updatedUser->name);
        $this->assertSame('081234567890', $updatedUser->phone);
    }

    public function test_it_strictly_ignores_email_role_status_and_id_in_input_data(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'name' => 'Nama Asli',
            'email' => 'original@example.com',
            'phone' => '081234567890',
            'role' => UserRole::User,
            'status' => UserStatus::Active,
        ]);

        Auth::login($user);

        $originalId = $user->id;

        $action = new UpdateProfile;
        $action->handle($user, [
            'name' => 'Nama Baru',
            'phone' => '089876543210',
            'email' => 'hacked@example.com',
            'role' => 'admin',
            'status' => 'blocked',
            'id' => '00000000-0000-0000-0000-000000000099',
        ]);

        $user->refresh();

        $this->assertSame($originalId, $user->id);
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('089876543210', $user->phone);
        $this->assertSame('original@example.com', $user->email);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_it_throws_authorization_exception_when_user_attempts_to_update_another_user_profile(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();

        Auth::login($user1);

        $this->expectException(AuthorizationException::class);

        $action = new UpdateProfile;
        $action->handle($user2, [
            'name' => 'Nama Usil',
            'phone' => '081234567890',
        ]);
    }
}
