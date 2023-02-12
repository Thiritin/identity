<?php

namespace App\Listeners;

class LogFailedLoginListener
{
    public function __construct()
    {
    }

    public function handle($event)
    {
        dd("benis");
    }
}
