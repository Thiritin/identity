<?php

namespace App\Domains\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class SecurityController extends Controller
{
    public function index()
    {
        return Inertia::render('Profile/Security/Index');
    }
}
