<?php

namespace App\Domains\Admin\Providers;

use App\Domains\Shared\Providers\DomainServiceProvider;

class AdminServiceProvider extends DomainServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register Admin domain services
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadDomainViews('admin');
    }

    /**
     * Get the domain name.
     *
     * @return string
     */
    protected function getDomainName(): string
    {
        return 'Admin';
    }
}
