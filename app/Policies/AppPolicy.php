<?php

namespace App\Policies;

use App\Models\App;
use App\Models\User;

class AppPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, App $app): bool
    {
        return $user->id === $app->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, App $app): bool
    {
        return $user->id === $app->user_id;
    }

    public function delete(User $user, App $app): bool
    {
        return $user->id === $app->user_id;
    }

    public function regenerateSecret(User $user, App $app): bool
    {
        return $user->id === $app->user_id;
    }
}
