<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use App\Models\OauthSession;
use App\Services\Hydra\Client;
use App\Services\Hydra\HydraRequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class SessionController extends Controller
{
    public function destroy(OauthSession $session): RedirectResponse
    {
        if ($session->user_id !== Auth::id()) {
            abort(403);
        }

        $hydra = new Client();

        try {
            $hydra->invalidateSession($session->session_id);
        } catch (HydraRequestException $e) {
            Log::warning('Failed to invalidate session in Hydra, removing locally', [
                'session_id' => $session->session_id,
                'message' => $e->getMessage(),
            ]);
        }

        $session->delete();

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans('security_sessions_ended'),
        ])->back();
    }

    public function destroyOthers(): RedirectResponse
    {
        $currentSessionId = Session::get('hydra_session_id');

        $sessions = Auth::user()->oauthSessions()
            ->where('session_id', '!=', $currentSessionId)
            ->get();

        $hydra = new Client();
        $failed = 0;

        foreach ($sessions as $session) {
            try {
                $hydra->invalidateSession($session->session_id);
                $session->delete();
            } catch (HydraRequestException $e) {
                Log::warning('Failed to revoke session', [
                    'session_id' => $session->session_id,
                    'message' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => trans('security_sessions_all_ended'),
        ])->back();
    }
}
