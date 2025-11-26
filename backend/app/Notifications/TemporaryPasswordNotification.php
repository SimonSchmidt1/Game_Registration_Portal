<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TemporaryPasswordNotification extends Notification implements ShouldQueue
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
            ->greeting('Ahoj ' . $notifiable->name . '!')
            ->line('Zaznamenali sme viacero neúspešných pokusov o prihlásenie do tvojho účtu.')
            ->line('Pre tvoje pohodlie sme vygenerovali **dočasné heslo**:')
            ->line('## ' . $this->temporaryPassword)
            ->line('Toto heslo je platné **15 minút**. Po prihlásení ti odporúčame okamžite zmeniť heslo v nastaveniach profilu.')
            ->action('Prihlásiť sa', config('app.frontend_url') . '/login')
            ->line('Ak si sa nepokúšal prihlásiť, kontaktuj administrátora.');
    }
}
