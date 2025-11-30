<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    // Removed ShouldQueue to send emails synchronously (no queue worker needed)

    public $verificationToken;

    /**
     * Create a new notification instance.
     */
    public function __construct($verificationToken)
    {
        $this->verificationToken = $verificationToken;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $base = config('app.frontend_url', config('app.url'));
        $verificationUrl = rtrim($base, '/') . '/verify-email?token=' . $this->verificationToken;

        return (new MailMessage)
            ->subject('Overte svoju e-mailovú adresu')
            ->view('emails.verify-email', [
                'verificationUrl' => $verificationUrl,
                'user' => $notifiable,
            ])
            ->text('emails.verify-email-text', [
                'verificationUrl' => $verificationUrl,
                'user' => $notifiable,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
