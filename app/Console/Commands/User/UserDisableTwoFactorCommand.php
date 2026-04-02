<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class UserDisableTwoFactorCommand extends Command
{
    protected $signature = 'user:disable-2fa {email}';

    protected $description = 'Disable two-factor authentication for a user';

    public function handle(): void
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('User not found.');

            return;
        }

        $user->resetTwoFactorAuth();
        $this->info("Two-factor authentication disabled for [{$user->email}].");
    }
}
