<?php

declare(strict_types=1);

namespace App\Domains\Identity\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = method_exists($notifiable, 'getEmailForPasswordReset')
            ? $notifiable->getEmailForPasswordReset()
            : (property_exists($notifiable, 'email') ? $notifiable->email : '');

        $url = url(route('identity.reset-password', [
            'token' => $this->token,
            'email' => $email,
        ], false));

        return (new MailMessage)
            ->subject(__('identity.reset_password.notification_subject'))
            ->line(__('identity.reset_password.notification_intro'))
            ->action(__('identity.reset_password.notification_action'), $url)
            ->line(__('identity.reset_password.notification_expire', [
                'count' => config('auth.passwords.users.expire', 60),
            ]))
            ->line(__('identity.reset_password.notification_outro'));
    }
}
