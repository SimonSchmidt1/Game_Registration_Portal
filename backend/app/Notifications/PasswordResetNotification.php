<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $resetUrl
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset hesla - Game Registration Portal')
            ->greeting('Ahoj ' . $notifiable->name . '!')
            ->line('Dostali sme požiadavku na reset hesla pre tvoj účet v **Game Registration Portal**.')
            ->line('Ak si to nebol ty, tento e-mail ignoruj. Heslo sa nezmení, pokiaľ neklikneš na tlačidlo nižšie.')
            ->action('Resetovať heslo', $this->resetUrl)
            ->line('Tento odkaz je platný iba **1 hodinu** a môže byť použitý iba **raz**.')
            ->line('Ak nefunguje tlačidlo, skopíruj tento odkaz do prehliadača:')
            ->line($this->resetUrl)
            ->salutation('S pozdravom, Tím Game Registration Portal');
    }
}
