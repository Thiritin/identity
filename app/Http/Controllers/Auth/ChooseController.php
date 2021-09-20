<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class ChooseController extends Controller
{
    public function __invoke()
    {
        return Inertia::render('Auth/Choose');
    }
}
