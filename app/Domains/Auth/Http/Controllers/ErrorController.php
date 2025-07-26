<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ErrorController extends Controller
{
    public function __invoke(Request $request)
    {
        return Inertia::render('Auth/Error', [
            'title' => $request->get('error'),
            'description' => $request->get('error_description'),
        ]);
    }
}
