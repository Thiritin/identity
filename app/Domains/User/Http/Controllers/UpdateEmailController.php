<?php

namespace App\Domains\User\Http\Controllers;

use App\Domains\User\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class UpdateEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'id' => [
                'required',
            ],
            'newEmail' => [
                'required',
            ],
        ]);
        $user = User::findByHashidOrFail($data['id']);
        $newMailFromCache = Cache::get('user:' . $data['id'] . ':newEmail');
        if (sha1($user->email) === $data['newEmail']) {
            return Inertia::render('Auth/VerifyEmailSuccess', [
                'user' => $user->only('name', 'email'),
                'hideUserInfo' => true,
            ]);
        }
        // Verify Hash of Cache and Request
        if ($data['newEmail'] !== sha1($newMailFromCache)) {
            return Inertia::render('Auth/VerifyEmailFailed', [
                'title' => 'Expired',
                'message' => 'The verification link you have used is invalid or expired. Please request a new verification link.',
            ]);
        }

        Log::info($user->id . ' has changed his E-Mail from ' . $user->email . ' to ' . $newMailFromCache);
        $user->update(['email' => $newMailFromCache]);

        return Inertia::render('Auth/VerifyEmailSuccess', [
            'user' => $user->only('name', 'email'),
            'hideUserInfo' => true,
        ]);
    }
}
