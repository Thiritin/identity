<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailCheckRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class EmailController extends Controller
{
    public function view(Request $request)
    {
        if ($request->has('login_challenge')) {
            Session::put('auth.email_flow.login_challenge', $request->get('login_challenge'));
        }

        if (! Session::has('auth.email_flow.login_challenge')) {
            return Redirect::route('login.apps.redirect', ['app' => 'portal']);
        }

        return Inertia::render('Auth/Email');
    }

    public function submit(EmailCheckRequest $request)
    {
        Session::put('auth.email_flow.email', $request->email);

        if (User::where('email', $request->email)->exists()) {
            return Redirect::route('auth.login.password.view');
        }

        return Redirect::route('auth.register.view');
    }
}
