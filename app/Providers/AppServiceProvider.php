<?php

namespace App\Providers;

use App\Providers\Socialite\SocialiteIdentityProvider;
use App\Domains\Auth\Services\Admin;
use App\Domains\Auth\Services\Client;
use Filament\Facades\Filament;
use Filament\Navigation\UserMenuItem;
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

        Http::macro('hydraAdmin', function () {
            return Http::baseUrl(config('services.hydra.admin'));
        });

        Http::macro('hydraPublic', function () {
            return Http::baseUrl(config('services.hydra.public'));
        });

        Http::macro('nextcloud', function () {
            return Http::baseUrl(config('services.nextcloud.baseUrl'))
                ->withHeader('OCS-APIRequest', 'true')
                ->withBasicAuth(config('services.nextcloud.username'), config('services.nextcloud.password'));
        });

        Filament::serving(function () {
            Filament::registerUserMenuItems([
                'logout-everywhere' => UserMenuItem::make()
                    ->icon('heroicon-o-arrow-right-circle')
                    ->url(route('dashboard'))
                    ->label('To User Interface'),
                'logout' => UserMenuItem::make()->url('/oauth2/sessions/logout')->label('Log out'),
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
