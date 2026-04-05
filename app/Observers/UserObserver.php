<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Webhooks\UserFieldMap;
use App\Services\Webhooks\WebhookDispatcher;

class UserObserver
{
    public function __construct(private readonly WebhookDispatcher $dispatcher)
    {
    }

    public function updated(User $user): void
    {
        $changedColumns = array_keys($user->getChanges());
        $original = $user->getOriginal();

        $externalChanged = [];
        $oldValues = [];

        foreach (UserFieldMap::MAP as $external => $column) {
            if (in_array($column, $changedColumns, true)) {
                $externalChanged[] = $external;
                $oldValues[$external] = $original[$column] ?? null;
            }
        }

        if ($externalChanged === []) {
            return;
        }

        $this->dispatcher->dispatchUserUpdated($user, $oldValues, $externalChanged);
    }
}
