<?php

namespace App\Domains\Shared\Providers;

use Illuminate\Support\ServiceProvider;

abstract class DomainServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    abstract public function register();

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    abstract public function boot();

    /**
     * Get the domain name.
     *
     * @return string
     */
    abstract protected function getDomainName(): string;

    /**
     * Get the domain path.
     *
     * @return string
     */
    protected function getDomainPath(): string
    {
        return app_path('Domains/' . $this->getDomainName());
    }

    /**
     * Load routes for the domain.
     *
     * @param string $routeFile
     * @return void
     */
    protected function loadDomainRoutes(string $routeFile): void
    {
        $routePath = base_path("routes/apps/{$routeFile}");
        
        if (file_exists($routePath)) {
            $this->loadRoutesFrom($routePath);
        }
    }

    /**
     * Load views for the domain.
     *
     * @param string $namespace
     * @return void
     */
    protected function loadDomainViews(string $namespace): void
    {
        $viewPath = resource_path($this->getDomainName() . '/views');
        
        if (is_dir($viewPath)) {
            $this->loadViewsFrom($viewPath, $namespace);
        }
    }
}
