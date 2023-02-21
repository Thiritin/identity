<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetQueuedNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail','log'];
    }

    public function toLog(User $notifiable)
    {
        activity()->by($notifiable)->log('mail.password-reset');
    }
}
