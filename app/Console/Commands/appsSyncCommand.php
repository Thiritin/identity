<?php

namespace App\Console\Commands;

use App\Models\App;
use Illuminate\Console\Command;

class appsSyncCommand extends Command
{
    protected $signature = 'apps:sync';

    protected $description = 'Refetches data field from hydra';

    public function handle()
    {
        $this->info('Updating Apps....');
        App::all()->each(function (App $app) {
            $this->info('Updating App: ' . $app->client_id);
            $app->data = \App\Services\Hydra\Models\App::find($app->client_id)->toArray();
            $app->saveQuietly();
        });
        $this->info('Done');
    }
}
