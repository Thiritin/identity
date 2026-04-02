<?php

namespace App\Listeners\Concerns;

use Illuminate\Support\Facades\App;

trait ChecksNextcloudEnvironment
{
    protected function shouldHandle(): bool
    {
        return ! App::isLocal() && ! app()->runningUnitTests();
    }
}
