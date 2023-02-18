<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;

class RegisteredUsers extends Widget
{
    protected static string $view = 'filament.widgets.registered-users';
    public int $registered;
    public int $unverified;
    public int $verified;
    public int $groupless;
    public int $grouped;

    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->registered = User::count();
        $this->unverified = User::whereNull('email_verified_at')->count();
        $this->verified = User::whereNotNull('email_verified_at')->count();
        $this->groupless = User::whereDoesntHave('groups')->count();
        $this->grouped = User::whereHas('groups')->count();
    }

}
