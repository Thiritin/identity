<?php

namespace App\Domains\User\Models;

use App\Domains\Staff\Enums\UserRankEnum;
use App\Domains\Staff\Models\Group;
use App\Domains\Staff\Models\GroupUser;
use App\Notifications\PasswordResetQueuedNotification;
use App\Notifications\UpdateEmailNotification;
use App\Notifications\VerifyEmailQueuedNotification;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use CausesActivity;
    use HasApiTokens;
    use HasFactory;
    use HasHashid;
    use HashidRouting;
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
        'username',
        'password',
        'profile_photo_path',
        'first_name',
        'last_name',
        'nickname',
        'phone_numbers',
        'telegram_username',
        'telegram_user_id',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province',
        'postal_code',
        'country',
        'date_of_birth',
        'languages',
        'credit_as',
        'joined_ef_year',
        'first_ef_year',
        'profile_visibility',
        'profile_completed_at',
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
        'date_of_birth' => 'date',
        'profile_completed_at' => 'datetime',
        'phone_numbers' => 'array',
        'languages' => 'array',
        'profile_visibility' => 'array',
        'joined_ef_year' => 'integer',
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

    /**
     * Get the user's global rank based on their department directorships
     */
    public function getRank(): UserRankEnum
    {
        // Director: User who is director of any department
        $isDirectorOfDepartment = $this->groups()
            ->where('type', 'department')
            ->wherePivot('level', 'director')
            ->exists();

        if ($isDirectorOfDepartment) {
            return UserRankEnum::Director;
        }

        // Staffer: User who is member of any department
        $isInDepartment = $this->groups()
            ->where('type', 'department')
            ->exists();

        if ($isInDepartment) {
            return UserRankEnum::Staffer;
        }

        // Default to Staffer for any group membership
        return UserRankEnum::Staffer;
    }

    /**
     * Check if user is a global director
     */
    public function isDirector(): bool
    {
        return $this->getRank() === UserRankEnum::Director;
    }

    /**
     * Get all groups where this user is a director
     */
    public function directorOfGroups()
    {
        return $this->groups()->wherePivotIn('level', ['director', 'division_director']);
    }

    /**
     * Get all groups where this user can manage users
     */
    public function canManageUsersInGroups()
    {
        return $this->groups()->where(function ($query) {
            $query->wherePivotIn('level', ['director', 'division_director'])
                  ->orWhere('can_manage_users', true);
        });
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
                    'can_manage_users',
                    'title',
                ]
            );
    }

    public function twoFactors(): HasOne
    {
        return $this->hasOne(TwoFactor::class);
    }

    public function webauthnCredentials(): HasMany
    {
        return $this->hasMany(WebauthnCredential::class);
    }

    public function hasWebauthnCredentials(): bool
    {
        return $this->webauthnCredentials()->exists();
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
        // Check if user is a member of the system_admins group
        return $this->groups()
            ->where('system_name', 'system_admins')
            ->exists();
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
