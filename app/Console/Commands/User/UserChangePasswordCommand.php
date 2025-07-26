<?php

namespace App\Console\Commands\User;

use App\Domains\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UserChangePasswordCommand extends Command
{
    protected $signature = 'user:change-password {email}';

    protected $description = 'Change Password';

    public function handle(): void
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();
        if (! $user) {
            $this->error('User not found');

            return;
        }
        $password = $this->secret('New Password');
        $user->password = Hash::make($password);
        $user->save();
        $this->info('Password changed');
    }
}
