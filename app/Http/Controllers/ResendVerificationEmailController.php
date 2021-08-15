<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ResendVerificationEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->user()->sendEmailVerificationNotification();
        return Inertia::render('Auth/VerifyEmail')->with('status','verification-link-sent');
    }
}
