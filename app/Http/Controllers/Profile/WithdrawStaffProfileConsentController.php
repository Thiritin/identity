<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Support\StaffProfile\ConsentNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

final class WithdrawStaffProfileConsentController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isStaff(), 403);

        $versionBefore = $user->staff_profile_consent_version;

        DB::transaction(function () use ($user) {
            // 1. Null all gated user columns.
            $nullColumns = array_fill_keys(ConsentNotice::GATED_USER_COLUMNS, null);

            $user->forceFill(array_merge($nullColumns, [
                'staff_profile_visibility'      => null,
                'staff_profile_consent_at'      => null,
                'staff_profile_consent_version' => null,
            ]))->save();

            // 2. Clear per-group credit_as across all of this user's group memberships.
            DB::table('group_user')
                ->where('user_id', $user->id)
                ->update(['credit_as' => null]);

            // 3. Delete all convention_attendee rows for this user.
            $user->conventions()->detach();
        });

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'version_at_withdrawal' => $versionBefore,
                'locale'                => app()->getLocale(),
            ])
            ->log('staff-profile-consent-withdrawn');

        return Redirect::route('my-data');
    }
}
