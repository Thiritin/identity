<?php

namespace App\Domains\Auth\Providers;

use App\Domains\Shared\Providers\DomainServiceProvider;

class AuthServiceProvider extends DomainServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register Auth domain services
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadDomainViews('auth');
    }

    /**
     * Get the domain name.
     *
     * @return string
     */
    protected function getDomainName(): string
    {
        return 'Auth';
    }
}
