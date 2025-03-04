<?php

namespace App\Observers;

use App\Models\App;
use Hash;

class AppObserver
{
    public function created(App $app)
    {
        $hydraApp = new \App\Services\Hydra\Models\App();
        $app->data = $hydraApp->create($app->data)->toArray();
        $app->client_secret = Hash::make($app->data['client_secret']);
        $app->client_id = $app->data['client_id'];
        $app->saveQuietly();
    }

    public function updated(App $app)
    {
        $app->data = \App\Services\Hydra\Models\App::find($app->client_id)->update($app->data);
        $app->client_id = $app->data['client_id'];
        if (! empty($app->data['client_secret'])) {
            $app->client_secret = Hash::make($app->data['client_secret']);
        }
        $app->saveQuietly();
    }

    public function deleted(App $app)
    {
        \App\Services\Hydra\Models\App::find($app->client_id)->delete();
    }
}
