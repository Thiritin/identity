<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Redirect;

class FrontChannelLogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        Auth::guard('web')->logout();

        return Redirect::route('auth.choose');
    }
}
