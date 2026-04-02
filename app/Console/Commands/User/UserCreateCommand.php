<?php

namespace App\Console\Commands\User;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UserCreateCommand extends Command
{
    protected $signature = 'user:create {name} {email} {--admin : Grant admin access}';

    protected $description = 'Create a new user';

    public function handle(): void
    {
        $email = $this->argument('email');

        if (User::where('email', $email)->exists()) {
            $this->error("User with email [{$email}] already exists.");

            return;
        }

        $password = $this->secret('Password');

        $user = User::create([
            'name' => $this->argument('name'),
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_admin' => $this->option('admin'),
        ]);

        $this->info("User [{$user->email}] created successfully." . ($user->is_admin ? ' (admin)' : ''));
    }
}
