<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\UserMenuItem;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->email.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinutes(30, 1)->by($request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        Http::macro('hydraAdmin', function () {
            return Http::baseUrl("http://hydra:4445");
        });

        Filament::serving(function () {
            Filament::registerUserMenuItems([
                'logout-everywhere' => UserMenuItem::make()->icon('heroicon-o-arrow-circle-right')->url(route('dashboard'))->label('To User Interface'),
                'logout' => UserMenuItem::make()->url('/oauth2/sessions/logout')->label('Log out'),
            ]);
        });
    }
}
