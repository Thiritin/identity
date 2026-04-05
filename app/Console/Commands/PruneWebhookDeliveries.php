<?php

namespace App\Console\Commands;

use App\Models\WebhookDelivery;
use Illuminate\Console\Command;

class PruneWebhookDeliveries extends Command
{
    protected $signature = 'webhooks:prune-deliveries';

    protected $description = 'Delete webhook_deliveries rows older than 7 days';

    public function handle(): int
    {
        $cutoff = now()->subDays(7);
        $deleted = WebhookDelivery::where('created_at', '<', $cutoff)->delete();
        $this->info("Pruned {$deleted} webhook deliveries older than {$cutoff->toDateTimeString()}.");
        return self::SUCCESS;
    }
}
