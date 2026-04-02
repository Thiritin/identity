<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;

class UserSetAdminCommand extends Command
{
    protected $signature = 'user:set-admin {email}';

    protected $description = 'Grant admin access to a user';

    public function handle(): void
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('User not found.');

            return;
        }

        $user->update(['is_admin' => true]);
        $this->info("User [{$user->email}] is now an admin.");
    }
}
