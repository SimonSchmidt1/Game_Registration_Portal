<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TemporaryPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $temporaryPassword
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Dočasné heslo - Game Registration Portal')
            ->view('emails.temporary-password', [
                'user' => $notifiable,
                'temporaryPassword' => $this->temporaryPassword,
            ]);
    }
}
