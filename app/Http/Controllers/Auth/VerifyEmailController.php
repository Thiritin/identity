<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use function redirect;

class VerifyEmailController extends Controller
{
    public function view(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Redirect::route('auth.oidc.login');
        }
        return Inertia::render('Auth/VerifyEmail');
    }

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return redirect(route('auth.oidc.login'));
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Redirect::route('dashboard');
        }
        $request->user()->sendEmailVerificationNotification();

        return Inertia::render('Auth/VerifyEmail')->with('status', 'verification-link-sent');
    }
}
