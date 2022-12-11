<?php

namespace App\Models;

use App\Notifications\PasswordResetQueuedNotification;
use App\Notifications\UpdateEmailNotification;
use App\Notifications\VerifyEmailQueuedNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Mtvs\EloquentHashids\HasHashid;
use Mtvs\EloquentHashids\HashidRouting;
use Spatie\Permission\Traits\HasRoles;
use Vinkla\Hashids\Facades\Hashids;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;
    use HasRoles;
    use HasApiTokens;
    use HasHashid;
    use HashidRouting;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path'
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
        'remember_token'
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
        'hashid'
    ];

    public function getHashId(): string
    {
        return Hashids::encode($this->id);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailQueuedNotification());
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PasswordResetQueuedNotification($token));
    }

    public function changeMail(string $newEmail)
    {
        $this->email = $newEmail;
        $this->notify(new UpdateEmailNotification($newEmail));
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class)
                    ->using('App\Models\GroupUser')
                    ->withPivot(
                        [
                            'level',
                        ]
                    );
    }

    public function appCan(string $scope)
    {
        if (!Auth::guard('api')->check()) {
            return true;
        }
        $auth = Auth::guard('api');
        return in_array($scope, $auth->getScopes(), true);
    }

    public function permCheck(string $ability)
    {
        $adminCheck = $this->can('admin.'.$ability);
        return $adminCheck || $this->scopeCheck($ability);
    }

    public function scopeCheck(string $ability)
    {
        $sanctumCheck = $this->tokenCan($ability);
        $appCheck = $this->appCan($ability);
        return ($sanctumCheck && $appCheck);
    }
}
