<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyEmailCodeNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class VerifyEmailController extends Controller
{
    public function view(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Redirect::route('dashboard');
        }

        $this->ensureCodeExists($request->user());

        return Inertia::render('Auth/VerifyCode', [
            'submitRoute' => 'verification.submit',
            'resendRoute' => 'verification.resend',
            'showLogout' => true,
        ]);
    }

    public function submit(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($request->user()->hasVerifiedEmail()) {
            return Redirect::route('dashboard');
        }

        $verifyData = Session::get('auth.verify_email_code');

        if (! $verifyData) {
            return Redirect::route('verification.notice');
        }

        if (now()->isAfter($verifyData['expires_at'])) {
            return back()->withErrors(['code' => __('verify_code_expired')]);
        }

        if (strtoupper($request->code) !== $verifyData['code']) {
            return back()->withErrors(['code' => __('verify_code_invalid')]);
        }

        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        Session::forget('auth.verify_email_code');

        return Redirect::intended(route('dashboard'));
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Redirect::route('dashboard');
        }

        $this->generateAndSendCode($request->user());

        return back()->with('status', 'code-resent');
    }

    private function ensureCodeExists(User $user): void
    {
        $verifyData = Session::get('auth.verify_email_code');

        if ($verifyData && now()->isBefore($verifyData['expires_at'])) {
            return;
        }

        $this->generateAndSendCode($user);
    }

    private function generateAndSendCode(User $user): void
    {
        $code = substr(str_shuffle(str_repeat('ABCDEFGHJKMNPQRSTUVWXYZ23456789', 3)), 0, 6);

        Session::put('auth.verify_email_code', [
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        $user->notify(new VerifyEmailCodeNotification($code));
    }
}
