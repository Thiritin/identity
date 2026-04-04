<?php

namespace App\Models;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Enums\StaffProfileVisibility;
use App\Enums\TwoFactorTypeEnum;
use App\Models\Concerns\HasHashid;
use App\Notifications\PasswordResetQueuedNotification;
use App\Notifications\UpdateEmailNotification;
use App\Services\Hydra\Client as HydraClient;
use App\Services\Hydra\HydraRequestException;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\HasApiTokens;
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
        'preferences',
        'firstname',
        'lastname',
        'birthdate',
        'phone',
        'spoken_languages',
        'credit_as',
        'staff_profile_visibility',
        'nda_verified_at',
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
        'firstname',
        'lastname',
        'birthdate',
        'phone',
        'telegram_id',
        'telegram_username',
        'staff_profile_visibility',
        'nda_verified_at',
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
        'is_developer' => 'boolean',
        'preferences' => 'array',
        'notification_preferences' => 'array',
        'suspended_at' => 'datetime',
        'birthdate' => 'date',
        'spoken_languages' => 'array',
        'staff_profile_visibility' => 'array',
        'nda_verified_at' => 'datetime',
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
                    'credit_as',
                    'can_manage_members',
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

    public function oauthSessions(): HasMany
    {
        return $this->hasMany(OauthSession::class);
    }

    public function conventions(): BelongsToMany
    {
        return $this->belongsToMany(Convention::class, 'convention_attendee')
            ->using(ConventionAttendee::class)
            ->withPivot('is_attended', 'is_staff')
            ->withTimestamps();
    }

    public function resetTwoFactorAuth()
    {
        $this->twoFactors()->delete();
    }

    public function deleteBackupCodesIfOrphaned(): void
    {
        $remainingMethods = $this->twoFactors()
            ->whereIn('type', [TwoFactorTypeEnum::TOTP, TwoFactorTypeEnum::YUBIKEY, TwoFactorTypeEnum::SECURITY_KEY])
            ->count();
        if ($remainingMethods === 0) {
            $this->twoFactors()->where('type', TwoFactorTypeEnum::BackupCodes)->delete();
        }
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function suspend(): void
    {
        $this->suspended_at = now();
        $this->remember_token = null;
        $this->save();

        $this->tokens()->delete();

        try {
            (new HydraClient())->invalidateAllSessions($this->hashid);
        } catch (HydraRequestException $e) {
            Log::warning('Failed to invalidate Hydra sessions during suspension', [
                'user' => $this->hashid,
                'message' => $e->getMessage(),
            ]);
        }

        activity()
            ->on($this)
            ->by(auth()->user())
            ->log('user-suspended');
    }

    public function unsuspend(): void
    {
        $this->suspended_at = null;
        $this->save();

        activity()
            ->on($this)
            ->by(auth()->user())
            ->log('user-unsuspended');
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
        if (Auth::guard('web')->check()) {
            return true;
        }
        $sanctumCheck = $this->tokenCan($ability);
        $appCheck = $this->appCan($ability);

        return $sanctumCheck || $appCheck;
    }

    public function canAccessPanel($panel): bool
    {
        return $this->is_admin && ! $this->isSuspended();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([
            'name',
            'email',
            'profile_photo_path',
            'firstname',
            'lastname',
            'birthdate',
            'phone',
            'telegram_username',
            'spoken_languages',
            'credit_as',
            'staff_profile_visibility',
            'preferences',
            'nda_verified_at',
        ]);
    }

    public function getHashidsConnection(): string
    {
        return 'user';
    }

    /**
     * Check if a viewer can see a specific PII field on this user's staff profile.
     */
    public function canViewStaffField(string $field, User $viewer): bool
    {
        $visibility = $this->staff_profile_visibility[$field] ?? null;
        $level = StaffProfileVisibility::tryFrom($visibility) ?? StaffProfileVisibility::AllStaff;

        return match ($level) {
            StaffProfileVisibility::AllStaff => $viewer->isStaff(),
            StaffProfileVisibility::MyDepartments => $this->sharesGroupWith($viewer),
            StaffProfileVisibility::LeadsAndDirectors => $viewer->hasStaffLevel(GroupUserLevel::leadOrManagerLevels()),
            StaffProfileVisibility::DirectorsOnly => $viewer->hasStaffLevel([GroupUserLevel::Director, GroupUserLevel::DivisionDirector]),
        };
    }

    public function sharesGroupWith(User $other): bool
    {
        $myGroupIds = $this->groups()
            ->whereIn('groups.type', [
                GroupTypeEnum::Department->value,
                GroupTypeEnum::Division->value,
                GroupTypeEnum::Team->value,
            ])
            ->pluck('groups.id');

        return $other->groups()->whereIn('groups.id', $myGroupIds)->exists();
    }

    public function hasStaffLevel(array $levels): bool
    {
        return $this->groups()
            ->wherePivotIn('level', array_map(fn ($l) => $l->value, $levels))
            ->exists();
    }
}
