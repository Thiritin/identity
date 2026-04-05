<?php

namespace App\Http\Controllers\Profile;

use App\Enums\StaffProfileVisibility;
use App\Http\Controllers\Controller;
use App\Support\StaffProfile\ConsentNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;

final class GrantStaffProfileConsentController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isStaff(), 403);

        $validated = $request->validate([
            'visibility'   => ['nullable', 'array'],
            'visibility.*' => [Rule::enum(StaffProfileVisibility::class)],
        ]);

        $data = [
            'staff_profile_consent_at'      => now(),
            'staff_profile_consent_version' => ConsentNotice::CURRENT_VERSION,
        ];

        if (! empty($validated['visibility'])) {
            $data['staff_profile_visibility'] = array_merge(
                $user->staff_profile_visibility ?? [],
                $validated['visibility'],
            );
        }

        $user->forceFill($data)->save();

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
