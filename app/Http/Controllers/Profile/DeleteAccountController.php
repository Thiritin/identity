<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Models\OauthSession;
use App\Services\Hydra\Client as HydraClient;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DeleteAccountController extends Controller
{
    public function __invoke(
        RegistrationService $registration,
        HydraClient $hydra,
    ): RedirectResponse {
        $user = Auth::user();

        try {
            if ($registration->hasActiveRegistration($user)) {
                return redirect()->back()->withErrors([
                    'delete' => trans('my_data_delete_blocked_registration'),
                ]);
            }
        } catch (RuntimeException $e) {
            Log::error('Registration check failed during account deletion', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->withErrors([
                'delete' => trans('my_data_delete_error'),
            ]);
        }

        try {
            $hydra->revokeAllConsentSessions($user->hashid);
            $hydra->invalidateAllSessions($user->hashid);
        } catch (\Exception $e) {
            Log::error('Hydra cleanup failed during account deletion', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        OauthSession::where('user_id', $user->id)->delete();

        Auth::logout();
        $user->delete();

        return redirect('/')->with('success', trans('my_data_delete_success'));
    }
}
