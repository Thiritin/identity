<?php

namespace App\Domains\User\Observers;

use App\Domains\User\Models\App;
use Hash;

class AppObserver
{
    public function created(App $app)
    {
        $hydraApp = new \App\Domains\Auth\Services\Models\App();
        $app->data = $hydraApp->create($app->data)->toArray();
        $app->client_secret = Hash::make($app->data['client_secret']);
        $app->client_id = $app->data['client_id'];
        $app->saveQuietly();
    }

    public function updated(App $app)
    {
        $app->data = \App\Domains\Auth\Services\Models\App::find($app->client_id)->update($app->data);
        $app->client_id = $app->data['client_id'];
        if (! empty($app->data['client_secret'])) {
            $app->client_secret = Hash::make($app->data['client_secret']);
        }
        $app->saveQuietly();
    }

    public function deleted(App $app)
    {
        \App\Domains\Auth\Services\Models\App::find($app->client_id)->delete();
    }
}
