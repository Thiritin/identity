<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FrontChannelLogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        Auth::guard('admin')->logout();
    }
}
