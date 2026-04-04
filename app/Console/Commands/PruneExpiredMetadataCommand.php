<?php

namespace App\Console\Commands;

use App\Models\UserAppMetadata;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneExpiredMetadataCommand extends Command
{
    protected $signature = 'metadata:prune-expired';

    protected $description = 'Delete user_app_metadata rows whose expires_at is in the past.';

    public function handle(): int
    {
        $count = UserAppMetadata::where('expires_at', '<', now())->delete();

        $this->info("Pruned {$count} expired metadata row(s).");
        Log::info("Pruned {$count} expired metadata row(s).");

        return self::SUCCESS;
    }
}
