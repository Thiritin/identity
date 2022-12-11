<?php

namespace App\Providers;

use App\Models\Group;
use App\Models\GroupUser;
use App\Policies\GroupPolicy;
use App\Policies\GroupUserPolicy;
use App\Services\Auth\AdminAuth;
use App\Services\Auth\ApiGuard;
use App\Services\Auth\TokenAuth;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        Group::class => GroupPolicy::class,
        GroupUser::class => GroupUserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });

        Auth::extend('admin', function ($app, $name, array $config) {
            return new AdminAuth(Auth::createUserProvider($config['provider']));
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return route('auth.password-reset.view', ['token' => $token, 'email' => $user->email]);
        });

        Auth::extend('hydra', function ($app, $name, array $config) {
            return new ApiGuard(Auth::createUserProvider($config['provider']), $app->make('request'));
        });
    }
}
