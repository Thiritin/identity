<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeEmailRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class ChangeEmailController extends Controller
{
    public function __invoke(ChangeEmailRequest $request): RedirectResponse
    {
        $user = $request->user();

        $done = RateLimiter::attempt(
            'changeEmail:' . $user->id,
            5,
            function () use ($user, $request) {
                $user->changeMail($request->validated('email'));

                return true;
            },
            900
        );

        if (! $done) {
            return Inertia::flash('toast', [
                'type' => 'error',
                'message' => trans('security_email_rate_limited'),
            ])->back();
        }

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans('security_email_sent'),
        ])->back();
    }
}
