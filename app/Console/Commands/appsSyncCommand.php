<?php

namespace App\Console\Commands;

use App\Domains\User\Models\App;
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
            $app->data = \App\Domains\Auth\Services\Models\App::find($app->client_id)->toArray();
            $app->saveQuietly();
        });
        $this->info('Done');
    }
}
