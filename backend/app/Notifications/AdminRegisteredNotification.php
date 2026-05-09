<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminRegisteredNotification extends Notification
{
    public function __construct(
        private string $plainPassword
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $loginUrl = rtrim(config('app.frontend_url', config('app.url')), '/') . '/login';

        return (new MailMessage)
            ->subject('Váš účet bol vytvorený - Game Registration Portal')
            ->view('emails.admin-registered', [
                'user'          => $notifiable,
                'plainPassword' => $this->plainPassword,
                'loginUrl'      => $loginUrl,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
