<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class NewProfilePhotoEvent
{
    use Dispatchable;

    public User $user;

    public string $path;

    public function __construct(User $user, string $path)
    {
        $this->user = $user;
        $this->path = $path;
    }
}
