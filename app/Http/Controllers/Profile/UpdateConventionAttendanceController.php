<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateOwnConventionAttendanceRequest;
use Illuminate\Support\Facades\Redirect;

class UpdateConventionAttendanceController extends Controller
{
    public function updateOwn(UpdateOwnConventionAttendanceRequest $request)
    {
        $user = $request->user();
        $conventionId = $request->validated('convention_id');
        $action = $request->validated('action');

        if ($action === 'add') {
            $user->conventions()->attach($conventionId, [
                'is_attended' => true,
                'is_staff' => false,
            ]);
        }

        if ($action === 'update') {
            $updates = [];

            if ($request->has('is_attended')) {
                $updates['is_attended'] = $request->boolean('is_attended');
            }

            if (! empty($updates)) {
                $user->conventions()->updateExistingPivot($conventionId, $updates);
            }
        }

        if ($action === 'remove') {
            $pivot = $user->conventions()->where('convention_id', $conventionId)->first();

            if ($pivot && $pivot->pivot->is_staff) {
                abort(403, 'Cannot remove: you are marked as staff for this convention.');
            }

            $user->conventions()->detach($conventionId);
        }

        return Redirect::back();
    }
}
