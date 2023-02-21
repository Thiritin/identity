<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class UpdateEmailNotification extends Notification implements ShouldQueue
{
    public $newEmail;
    use Queueable;

    public function __construct($newEmail)
    {
        $this->newEmail = $newEmail;
    }

    public function via($notifiable): array
    {
        return ['mail','log'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute('settings.update-profile.email.update', now()->addHours(4), ['newEmail' => $this->newEmail]);
        return (new MailMessage())
            ->subject(Lang::get('Verify Email Address'))
            ->line(Lang::get('Please click the button below to verify your email address.'))
            ->action(Lang::get('Verify Email Address'), $url)
            ->line(Lang::get('You have requested to change your email for your Eurofurence account. This email is valid for 4 hours.'));
    }

    public function toLog(User $notifiable)
    {
        activity()->by($notifiable)->log('mail.update-email');
    }

}
