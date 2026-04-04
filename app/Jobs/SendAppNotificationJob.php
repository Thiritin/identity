<?php

namespace App\Jobs;

use App\Models\NotificationType;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Notifications\NotificationPreferenceResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAppNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $typeId,
        public int $userId,
        public array $payload,
    ) {}

    public function handle(NotificationPreferenceResolver $resolver): void
    {
        $type = NotificationType::find($this->typeId);
        if (! $type || $type->disabled) {
            logger()->info('notification send skipped: type missing or disabled', [
                'type_id' => $this->typeId,
            ]);

            return;
        }

        $user = User::find($this->userId);
        if (! $user) {
            logger()->info('notification send skipped: user missing', [
                'user_id' => $this->userId,
            ]);

            return;
        }

        $channels = $resolver->resolve($user, $type);
        if (empty($channels)) {
            logger()->info('notification send skipped: no channels after resolution', [
                'user_id' => $this->userId,
                'type_id' => $this->typeId,
            ]);

            return;
        }

        $user->notify(new AppNotification($type, $this->payload, $channels));
    }
}
