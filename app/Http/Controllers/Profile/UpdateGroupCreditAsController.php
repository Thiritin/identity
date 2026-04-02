<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateGroupCreditAsRequest;
use Illuminate\Support\Facades\Redirect;

class UpdateGroupCreditAsController extends Controller
{
    public function __invoke(UpdateGroupCreditAsRequest $request)
    {
        $user = $request->user();

        $user->update(['credit_as' => $request->input('credit_as')]);

        foreach ($request->input('groups', []) as $entry) {
            $user->groups()->updateExistingPivot(
                $entry['group_id'],
                ['credit_as' => $entry['credit_as']]
            );
        }

        return Redirect::route('settings.profile');
    }
}
