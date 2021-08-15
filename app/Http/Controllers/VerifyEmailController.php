<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect(route('dashboard'));
    }
}
