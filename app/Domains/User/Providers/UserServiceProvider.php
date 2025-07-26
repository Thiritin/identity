<?php

namespace App\Domains\User\Providers;

use App\Domains\Shared\Providers\DomainServiceProvider;

class UserServiceProvider extends DomainServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register User domain services
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadDomainViews('user');
    }

    /**
     * Get the domain name.
     *
     * @return string
     */
    protected function getDomainName(): string
    {
        return 'User';
    }
}
