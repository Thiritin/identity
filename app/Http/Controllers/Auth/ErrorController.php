<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ErrorController extends Controller
{
    public function __invoke(Request $request)
    {
        return Inertia::render('Auth/Error', [
            'error' => $request->get('error', 'unknown'),
            'hideUserInfo' => true,
        ]);
    }
}
