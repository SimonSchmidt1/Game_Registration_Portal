<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailNotification extends Notification
{
    use Queueable;

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
        $verificationUrl = env('FRONTEND_URL') . '/verify-email?token=' . $this->verificationToken;

        return (new MailMessage)
            ->subject('Overte svoju e-mailovú adresu')
            ->greeting('Ahoj ' . $notifiable->name . '!')
            ->line('Ďakujeme za registráciu v Game Registration Portal.')
            ->line('Prosím, overte svoju e-mailovú adresu kliknutím na tlačidlo nižšie:')
            ->action('Overiť e-mail', $verificationUrl)
            ->line('Ak ste nevytvárali účet, ignorujte tento e-mail.')
            ->salutation('S pozdravom, tím Game Portal');
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
