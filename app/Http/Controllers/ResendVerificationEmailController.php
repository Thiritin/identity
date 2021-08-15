<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ResendVerificationEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
