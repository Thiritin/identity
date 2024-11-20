<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class UpdateEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public $newEmail, public $hashId) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(Lang::get('Verify Email Address'))
            ->line(Lang::get('Please click the button below to verify your email address.'))
            ->action(Lang::get('Verify Email Address'), $this->verificationUrl())
            ->line(Lang::get('You have requested to change your email for your Eurofurence account. This email is valid for 4 hours.'));
    }

    protected function verificationUrl(): string
    {
        return URL::temporarySignedRoute(
            'settings.update-profile.email.update',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $this->hashId,
                'newEmail' => sha1($this->newEmail),
            ]
        );
    }
}
