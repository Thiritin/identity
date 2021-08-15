<?php

namespace App\Providers;

use App\Services\Auth\AdminAuth;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::extend('admin', function ($app, $name, array $config) {
            return new AdminAuth(Auth::createUserProvider($config['provider']));
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return route('auth.password-reset.view',['token' => $token, 'email' => $user->email]);
        });
    }
}
