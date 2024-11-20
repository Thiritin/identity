<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redirect;

class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request)
    {
        $user = $request->user();

        if ($user->name !== $request->get('name')) {
            activity()->by($user)->log('name.change');
            $user->update(['name' => $request->get('name')]);
        }

        if ($user->email !== $request->get('email')) {

            $done = RateLimiter::attempt(
                'emailVerify:' . $user->id,
                5,
                function () use ($user, $request) {
                    $this->validate($request, ['unique:users,email']);
                    activity()->by($user)->log('mail.change-email');
                    $user->changeMail($request->get('email'));

                    return Redirect::route('settings.profile')->with('message', 'emailVerify');
                },
                900
            );
            if (! $done) {
                return Redirect::route('settings.profile')->with('message', 'emailTooMany');
            }
        }

        return Redirect::route('settings.profile');
    }
}
