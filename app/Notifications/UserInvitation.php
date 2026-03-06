<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification
{
    use Queueable;

    public function __construct(
        protected string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ], false));

        return (new MailMessage)
            ->subject('Einladung zur td Academy')
            ->greeting("Hallo {$notifiable->name},")
            ->line('Du wurdest zur td Academy eingeladen. Bitte klicke auf den Button unten, um dein Passwort zu setzen und deinen Zugang zu aktivieren.')
            ->action('Passwort setzen', $url)
            ->line('Dieser Link ist 60 Minuten gültig.')
            ->salutation('Viele Grüße, dein td Academy Team');
    }
}
