<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GroupMember
{
    public function handle(Request $request, Closure $next, $groupSystemName)
    {
        if ($request->user()->groups()->where('system_name', $groupSystemName)->doesntExist()) {
            abort(403);
        }

        return $next($request);
    }
}
