<?php

namespace App\Domains\Staff\Providers;

use App\Domains\Shared\Providers\DomainServiceProvider;

class StaffServiceProvider extends DomainServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register Staff domain services
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadDomainViews('staff');
    }

    /**
     * Get the domain name.
     *
     * @return string
     */
    protected function getDomainName(): string
    {
        return 'Staff';
    }
}
