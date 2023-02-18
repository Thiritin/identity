<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ClearUnverifiedCommand extends Command
{
    protected $signature = 'clear:unverified';

    protected $description = 'If a user has not verified their email since 24 hours after account creation. Their account will be deleted.';

    public function handle()
    {
        User::whereNull('email_verified_at')->where('created_at', '<', now()->subDay())->delete();
    }
}
