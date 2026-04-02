<?php

namespace App\Models;

use App\Models\App;
use App\Notifications\PasswordResetQueuedNotification;
use App\Notifications\UpdateEmailNotification;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Concerns\HasHashid;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use CausesActivity;
    use HasApiTokens;
    use HasFactory;
    use HasHashid;
    use LogsActivity;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_changed_at',
        'profile_photo_path',
        'is_admin',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'is_admin' => 'boolean',
        'preferences' => 'array',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    public function inGroup(int $id): bool
    {
        return $this->groups()->where('id', $id)->exists();
    }

    public function isStaff(): bool
    {
        $staffGroup = Group::where('system_name', 'staff')->first();

        if (! $staffGroup) {
            return false;
        }

        return $this->inGroup($staffGroup->id);
    }

    public function sendEmailVerificationNotification(): void
    {
        // Verification is handled by 6-digit code in RegisterVerifyController.
        // This method is intentionally empty to prevent the default link-based email.
    }

    public function sendPasswordResetNotification($token): void
    {
        activity()
            ->by($this)
            ->log('mail-password-reset');
        $this->notify(new PasswordResetQueuedNotification($token));
    }

    public function changeMail(string $newEmail)
    {
        $this->email = $newEmail;
        activity()
            ->by($this)
            ->log('mail-change-mail');

        Cache::put(
            'user:' . $this->hashid . ':newEmail',
            $newEmail,
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)));
        Notification::route('mail', $newEmail)
            ->notify(new UpdateEmailNotification($newEmail, $this->hashid));
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class)
            ->using(GroupUser::class)
            ->withPivot(
                [
                    'level',
                    'title',
                ]
            );
    }

    public function apps(): HasMany
    {
        return $this->hasMany(App::class);
    }

    public function twoFactors(): HasMany
    {
        return $this->hasMany(TwoFactor::class);
    }

    public function resetTwoFactorAuth()
    {
        $this->twoFactors()->delete();
    }

    public function appCan(string $scope)
    {
        $auth = Auth::guard('api');

        return in_array($scope, $auth->getScopes(), true);
    }

    public function permCheck(string $ability)
    {
        $adminCheck = $this->can('admin.' . $ability);

        return $adminCheck || $this->scopeCheck($ability);
    }

    public function scopeCheck(string $ability)
    {
        if (Auth::guard('web')->check() || Auth::guard('staff')->check()) {
            return true;
        }
        $sanctumCheck = $this->tokenCan($ability);
        $appCheck = $this->appCan($ability);

        return $sanctumCheck || $appCheck;
    }

    public function canAccessPanel($panel): bool
    {
        return $this->is_admin;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email', 'profile_photo_path']);
    }

    public function getHashidsConnection(): string
    {
        return 'user';
    }
}
