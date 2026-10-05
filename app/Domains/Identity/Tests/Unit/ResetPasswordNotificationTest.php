<?php

declare(strict_types=1);

namespace App\Domains\Identity\Tests\Unit;

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Notifications\ResetPasswordNotification;
use Tests\TestCase;

final class ResetPasswordNotificationTest extends TestCase
{
    public function test_it_builds_mail_message_in_indonesian(): void
    {
        $user = new User([
            'email' => 'user@example.com',
            'name' => 'Budi',
        ]);

        $notification = new ResetPasswordNotification('test-token-123');

        $this->assertSame(['mail'], $notification->via($user));

        $mail = $notification->toMail($user);

        $this->assertSame(__('identity.reset_password.notification_subject'), $mail->subject);
        $this->assertStringContainsString('test-token-123', $mail->actionUrl);
        $this->assertStringContainsString('user%40example.com', $mail->actionUrl);
    }
}
