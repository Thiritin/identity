<?php

namespace App\Providers;

use App\Providers\Socialite\SocialiteIdentityProvider;
use App\Services\Hydra\Admin;
use App\Services\Hydra\Client;
use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {}

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->email . $request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinutes(30, 1)->by($request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Http::macro('hydraAdmin', function (): \Illuminate\Http\Client\PendingRequest {
            return Http::baseUrl(config('services.hydra.admin'));
        });

        Http::macro('hydraPublic', function (): \Illuminate\Http\Client\PendingRequest {
            return Http::baseUrl(config('services.hydra.public'));
        });

        Http::macro('nextcloud', function (): \Illuminate\Http\Client\PendingRequest {
            return Http::baseUrl(config('services.nextcloud.baseUrl'))
                ->withHeader('OCS-APIRequest', 'true')
                ->withBasicAuth(config('services.nextcloud.username'), config('services.nextcloud.password'));
        });

        Filament::serving(function () {
            Filament::registerUserMenuItems([
                'logout-everywhere' => MenuItem::make()
                    ->icon('heroicon-o-arrow-right-circle')
                    ->url(route('dashboard'))
                    ->label('To User Interface'),
                'logout' => MenuItem::make()->url('/oauth2/sessions/logout')->label('Log out'),
            ]);
        });

        $this->app->bind(Admin::class, function () {
            return new Admin();
        });

        $this->app->bind(Client::class, function () {
            return new Client();
        });

        $socialite = $this->app->make(Factory::class);
        $socialite->extend('idp-portal', function () use ($socialite) {
            return $socialite->buildProvider(SocialiteIdentityProvider::class, config('services.apps.portal'));
        });
        $socialite->extend('idp-admin', function () use ($socialite) {
            return $socialite->buildProvider(SocialiteIdentityProvider::class, config('services.apps.admin'));
        });
        $socialite->extend('idp-staff', function () use ($socialite) {
            return $socialite->buildProvider(SocialiteIdentityProvider::class, config('services.apps.staff'));
        });
    }
}
