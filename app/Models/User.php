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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
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
        'pronouns',
        'birthdate',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_telegram',
        'spoken_languages',
        'credit_as',
        'staff_profile_visibility',
        'nda_checked_at',
        'staff_profile_consent_at',
        'staff_profile_consent_version',
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
        'pronouns',
        'birthdate',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_telegram',
        'telegram_id',
        'telegram_username',
        'staff_profile_visibility',
        'nda_checked_at',
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
        'is_convention_manager' => 'boolean',
        'is_developer' => 'boolean',
        'preferences' => 'array',
        'notification_preferences' => 'array',
        'suspended_at' => 'datetime',
        'anonymized_at' => 'datetime',
        'birthdate' => 'date',
        'spoken_languages' => 'array',
        'staff_profile_visibility' => 'array',
        'nda_checked_at' => 'datetime',
        'staff_profile_consent_at' => 'datetime',
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
        return $this->inSystemGroup('staff');
    }

    public function isTeamLead(): bool
    {
        return $this->inSystemGroup('team_leads');
    }

    public function isDirector(): bool
    {
        return $this->inSystemGroup('directors');
    }

    public function isDivisionDirector(): bool
    {
        return $this->inSystemGroup('division_directors');
    }

    private function inSystemGroup(string $systemName): bool
    {
        $group = Group::where('system_name', $systemName)->first();

        if (! $group) {
            return false;
        }

        return $this->inGroup($group->id);
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

    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }

    /**
     * GDPR anonymization: scramble PII, revoke all credentials, and retain only
     * the primary key so foreign keys (activity log, group history, etc.) stay
     * valid. The row is flagged as suspended so the existing login gate blocks
     * any further access.
     */
    public function anonymize(): void
    {
        $id = $this->id;

        $this->forceFill([
            'name' => 'deleted-user-' . $id,
            'email' => 'deleted-' . $id . '@deleted.invalid',
            'email_verified_at' => null,
            'password' => Hash::make(Str::random(64)),
            'password_changed_at' => now(),
            'remember_token' => null,
            'profile_photo_path' => null,
            'preferences' => null,
            'firstname' => null,
            'lastname' => null,
            'pronouns' => null,
            'birthdate' => null,
            'phone' => null,
            'telegram_id' => null,
            'telegram_username' => null,
            'spoken_languages' => null,
            'address_line1' => null,
            'address_line2' => null,
            'city' => null,
            'postal_code' => null,
            'country' => null,
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
            'emergency_contact_telegram' => null,
            'credit_as' => null,
            'staff_profile_visibility' => null,
            'staff_profile_consent_at' => null,
            'staff_profile_consent_version' => null,
            'nda_checked_at' => null,
            'suspended_at' => now(),
            'anonymized_at' => now(),
        ])->save();

        $this->tokens()->delete();
        $this->twoFactors()->delete();
        $this->groups()->detach();
        $this->conventions()->detach();

        activity()
            ->on($this)
            ->log('user-anonymized');
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
        if ($this->isSuspended()) {
            return false;
        }

        return match ($panel->getId()) {
            'convention' => $this->is_admin || $this->is_convention_manager,
            default => $this->is_admin,
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([
            'name',
            'email',
            'profile_photo_path',
            'firstname',
            'lastname',
            'pronouns',
            'birthdate',
            'phone',
            'address_line1',
            'address_line2',
            'city',
            'postal_code',
            'country',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_telegram',
            'telegram_username',
            'spoken_languages',
            'credit_as',
            'staff_profile_visibility',
            'preferences',
            'nda_checked_at',
        ]);
    }

    public function getHashidsConnection(): string
    {
        return 'user';
    }

    /**
     * Per-key default visibility. Keys absent from this map fall through to AllStaff.
     */
    private const STAFF_FIELD_DEFAULT_VISIBILITY = [
        'address' => StaffProfileVisibility::DirectorsOnly,
    ];

    /**
     * Check if a viewer can see a specific PII field on this user's staff profile.
     */
    public function canViewStaffField(string $field, User $viewer): bool
    {
        $stored = $this->staff_profile_visibility[$field] ?? null;
        $level = ($stored !== null ? StaffProfileVisibility::tryFrom($stored) : null)
            ?? self::STAFF_FIELD_DEFAULT_VISIBILITY[$field]
            ?? StaffProfileVisibility::AllStaff;

        return match ($level) {
            StaffProfileVisibility::AllStaff => $viewer->isStaff(),
            StaffProfileVisibility::MyDepartments => $this->sharesGroupWith($viewer),
            StaffProfileVisibility::LeadsAndDirectors => $viewer->hasStaffLevel(GroupUserLevel::leadOrManagerLevels()),
            StaffProfileVisibility::DirectorsOnly => $viewer->hasStaffLevel([GroupUserLevel::Director, GroupUserLevel::DivisionDirector]),
        };
    }

    /**
     * Default visibility values keyed by visibility key, exposed for the frontend.
     *
     * @return array<string, string>
     */
    public static function staffFieldDefaultVisibility(): array
    {
        return collect(self::STAFF_FIELD_DEFAULT_VISIBILITY)
            ->map(fn (StaffProfileVisibility $v) => $v->value)
            ->all();
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

    public function hasStaffProfileConsent(): bool
    {
        return $this->staff_profile_consent_at !== null;
    }

    public function hasCurrentStaffProfileConsent(): bool
    {
        return $this->staff_profile_consent_version === \App\Support\StaffProfile\ConsentNotice::CURRENT_VERSION;
    }
}
