<?php

namespace App\Providers;

use App\Events\AppLoginEvent;
use App\Listeners\LogFailedLoginListener;
use App\Listeners\LogUserAppLoginListener;
use App\Listeners\LogUserLockoutListener;
use App\Listeners\LogUserLoginListener;
use App\Listeners\LogUserPasswordResetListener;
use App\Listeners\LogUserRegisteredListener;
use App\Listeners\LogUserVerifiedListener;
use App\Models\App;
use App\Models\Group;
use App\Models\GroupUser;
use App\Observers\AppObserver;
use App\Observers\GroupObserver;
use App\Observers\GroupUserObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            LogUserRegisteredListener::class,
        ],
        Failed::class => [
            LogFailedLoginListener::class,
        ],
        Lockout::class => [
            LogUserLockoutListener::class,
        ],
        Verified::class => [
            LogUserVerifiedListener::class,
        ],
        Login::class => [
            LogUserLoginListener::class,
        ],
        PasswordReset::class => [
            LogUserPasswordResetListener::class,
        ],
        AppLoginEvent::class => [
            LogUserAppLoginListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        App::observe(AppObserver::class);
        Group::observe(GroupObserver::class);
        GroupUser::observe(GroupUserObserver::class);
    }
}
