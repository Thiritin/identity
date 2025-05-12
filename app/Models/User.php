<?php

namespace App\Models;

use App\Notifications\PasswordResetQueuedNotification;
use App\Notifications\UpdateEmailNotification;
use App\Notifications\VerifyEmailQueuedNotification;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\HasApiTokens;
use Mtvs\EloquentHashids\HasHashid;
use Mtvs\EloquentHashids\HashidRouting;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $profile_photo_path
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $actions
 * @property-read int|null $actions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read mixed $hashid
 * @property-read \App\Models\GroupUser|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Group> $groups
 * @property-read int|null $groups_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @property-read \App\Models\TwoFactor|null $twoFactors
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User check()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use CausesActivity;
    use HasApiTokens;
    use HasFactory;
    use HasHashid;
    use HashidRouting;
    use HasRoles;
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
        'profile_photo_path',
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
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'hashid',
    ];

    public function inGroup(int $id): bool
    {
        return $this->groups()->where('level', '!=', 'invited')->where('id', $id)->exists();
    }

    public function isStaff(): bool
    {
        if (empty(config('groups.staff'))) {
            return false;
        }

        return $this->inGroup(config('groups.staff'));
    }

    public function sendEmailVerificationNotification(): void
    {
        activity()
            ->by($this)
            ->log('mail-verify-email');
        $this->notify(new VerifyEmailQueuedNotification());
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
                ]
            );
    }

    public function twoFactors(): HasOne
    {
        return $this->hasOne(TwoFactor::class);
    }

    public function resetTwoFactorAuth()
    {
        $this->twoFactors()->delete();
    }

    public function appCan(string $scope)
    {
        /** @var \App\Services\Auth\ApiGuard */
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
        return $this->hasRole('superadmin');
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
