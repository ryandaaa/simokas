<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Actions\SendPasswordResetLink;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class SendPasswordResetLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_notification_when_user_exists(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'registered@example.com']);

        $action = new SendPasswordResetLink;
        $status = $action->handle('registered@example.com');

        $this->assertSame('passwords.sent', $status);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_it_does_not_send_notification_when_user_does_not_exist_but_returns_same_status(): void
    {
        Notification::fake();

        $action = new SendPasswordResetLink;
        $status = $action->handle('unregistered@example.com');

        $this->assertSame('passwords.sent', $status);
        Notification::assertNothingSent();
    }
}
