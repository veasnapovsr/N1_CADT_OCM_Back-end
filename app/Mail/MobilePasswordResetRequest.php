<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MobilePasswordResetRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset Request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-request',
            with: [
                'user' => $this->user,
                'token' => $this->user->forgot_password_token,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}