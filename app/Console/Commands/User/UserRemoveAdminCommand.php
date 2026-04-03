<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class UserRemoveAdminCommand extends Command
{
    protected $signature = 'user:remove-admin {email}';

    protected $description = 'Remove admin access from a user';

    public function handle(): void
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('User not found.');

            return;
        }

        $user->is_admin = false;
        $user->save();
        $this->info("User [{$user->email}] is no longer an admin.");
    }
}
