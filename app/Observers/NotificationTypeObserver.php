<?php

namespace App\Observers;

use App\Models\NotificationType;
use DomainException;

class NotificationTypeObserver
{
    public function updating(NotificationType $type): void
    {
        if ($type->isDirty('key')) {
            throw new DomainException('NotificationType key is immutable');
        }

        if ($type->isDirty('category')) {
            throw new DomainException('NotificationType category is immutable');
        }
    }
}
