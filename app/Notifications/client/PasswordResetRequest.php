<?php

namespace App\Notifications\client;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetRequest extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $displayName = trim((string) (($notifiable->firstname ?? '').' '.($notifiable->lastname ?? '')));

        return (new MailMessage())
            ->subject('Password Reset Request')
            ->greeting('Hello '.($displayName !== '' ? $displayName : ($notifiable->name ?? 'User')).',')
            ->line('We received a request to reset your password.')
            ->line('Use this confirmation code to continue: '.($notifiable->forgot_password_token ?? ''))
            ->line('If you did not request a password reset, you can ignore this email.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'forgot_password_token' => $notifiable->forgot_password_token ?? null,
        ];
    }
}