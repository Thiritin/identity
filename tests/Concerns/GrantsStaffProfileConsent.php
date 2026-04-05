<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Support\StaffProfile\ConsentNotice;

trait GrantsStaffProfileConsent
{
    protected function grantStaffProfileConsent(User $user): User
    {
        $user->forceFill([
            'staff_profile_consent_at'      => now(),
            'staff_profile_consent_version' => ConsentNotice::CURRENT_VERSION,
        ])->save();

        return $user->fresh();
    }
}
