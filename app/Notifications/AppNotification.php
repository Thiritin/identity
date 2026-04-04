<?php

namespace App\Notifications;

use App\Enums\NotificationChannel;
use App\Models\NotificationType;
use App\Notifications\Channels\AppDatabaseChannel;
use App\Notifications\Channels\AppTelegramChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $channels
     */
    public function __construct(
        public NotificationType $type,
        public array $payload,
        public array $channels,
    ) {}

    public function via(mixed $notifiable): array
    {
        return array_map(function (string $channel) {
            return match ($channel) {
                NotificationChannel::Email->value => 'mail',
                NotificationChannel::Telegram->value => AppTelegramChannel::class,
                NotificationChannel::Database->value => AppDatabaseChannel::class,
            };
        }, $this->channels);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $mail = new MailMessage();
        $mail->subject($this->payload['subject']);

        if (! empty($this->payload['html'])) {
            $mail->view('mail.raw-html', [
                'subject' => $this->payload['subject'],
                'html' => $this->payload['html'],
            ]);
            return $mail;
        }

        $mail->line($this->payload['body']);

        if (! empty($this->payload['cta'])) {
            $mail->action($this->payload['cta']['label'], $this->payload['cta']['url']);
        }

        return $mail;
    }

    public function toAppTelegram(mixed $notifiable): string
    {
        $subject = $this->escapeMarkdown($this->payload['subject']);
        $body = $this->escapeMarkdown($this->payload['body']);

        $message = "*{$subject}*\n\n{$body}";

        if (! empty($this->payload['cta'])) {
            $label = $this->escapeMarkdown($this->payload['cta']['label']);
            $url = $this->payload['cta']['url'];
            $message .= "\n\n[{$label}]({$url})";
        }

        return $message;
    }

    public function toAppDatabase(mixed $notifiable): array
    {
        return [
            'app_id' => $this->type->app_id,
            'notification_type_id' => $this->type->id,
            'subject' => $this->payload['subject'],
            'body' => $this->payload['body'],
            'cta_label' => $this->payload['cta']['label'] ?? null,
            'cta_url' => $this->payload['cta']['url'] ?? null,
        ];
    }

    private function escapeMarkdown(string $text): string
    {
        return str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $text);
    }
}
