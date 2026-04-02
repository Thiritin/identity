<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Spatie\Honeypot\SpamResponder\SpamResponder;
use Symfony\Component\HttpFoundation\Response;

class HoneypotSpamResponder implements SpamResponder
{
    public function respond(Request $request, Closure $next): Response
    {
        return back()->withErrors(['honeypot' => 'Request rejected.']);
    }
}
