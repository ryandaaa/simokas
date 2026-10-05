<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Enums\UserStatus;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class UserTest extends TestCase
{
    public function test_user_casts_attributes(): void
    {
        $user = new User([
            'id' => '00000000-0000-0000-0000-000000000001',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret',
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertArrayHasKey('email_verified_at', $user->getCasts());
        $this->assertArrayHasKey('password', $user->getCasts());
        $this->assertArrayHasKey('role', $user->getCasts());
        $this->assertArrayHasKey('status', $user->getCasts());
        $this->assertArrayHasKey('accepted_terms_at', $user->getCasts());
    }

    public function test_user_helper_methods_and_roles(): void
    {
        $user = new User([
            'role' => 'user',
            'status' => 'active',
        ]);

        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isPending());
        $this->assertFalse($user->isBlocked());
        $this->assertTrue($user->isUser());
        $this->assertFalse($user->isInspector());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->canBuy());
        $this->assertTrue($user->canSell());
        $this->assertTrue($user->canRequestInspection());

        $inspector = new User([
            'role' => 'inspector',
            'status' => 'pending',
        ]);

        $this->assertTrue($inspector->isPending());
        $this->assertFalse($inspector->isActive());
        $this->assertTrue($inspector->isInspector());
        $this->assertFalse($inspector->isUser());
        $this->assertFalse($inspector->canBuy());
        $this->assertFalse($inspector->canSell());
        $this->assertFalse($inspector->canRequestInspection());

        $admin = new User([
            'role' => 'admin',
            'status' => 'blocked',
        ]);

        $this->assertTrue($admin->isBlocked());
        $this->assertTrue($admin->isAdmin());
    }

    public function test_send_password_reset_notification(): void
    {
        Notification::fake();

        $user = new User([
            'email' => 'user@example.com',
        ]);

        $user->sendPasswordResetNotification('token-abc');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }
}
