<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function show(User $user)
    {
        Gate::authorize('update', $user);
    }
}
