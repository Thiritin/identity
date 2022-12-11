<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class SecurityController extends Controller
{
    public function index()
    {
        clock(Session::all());
        return Inertia::render('Profile/Security/Index');
    }
}
