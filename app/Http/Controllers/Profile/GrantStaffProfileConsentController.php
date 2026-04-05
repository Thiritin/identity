<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Support\StaffProfile\ConsentNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

final class GrantStaffProfileConsentController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isStaff(), 403);

        $user->forceFill([
            'staff_profile_consent_at'      => now(),
            'staff_profile_consent_version' => ConsentNotice::CURRENT_VERSION,
        ])->save();

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'version' => ConsentNotice::CURRENT_VERSION,
                'locale'  => app()->getLocale(),
            ])
            ->log('staff-profile-consent-granted');

        return Redirect::route('settings.profile');
    }
}
