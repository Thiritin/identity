<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UpdateEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'newEmail' => "required",
        ]);
        Log::info($request->user()->id . " has changed his E-Mail from " . $request->user()->email . " to " . $data['newEmail']);
        $request->user()->update(['email' => $data['newEmail']]);
        return redirect(route('settings.profile'));
    }
}
