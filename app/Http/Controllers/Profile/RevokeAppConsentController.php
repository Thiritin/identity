<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\Hydra\Client as HydraClient;
use App\Services\Hydra\HydraRequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RevokeAppConsentController extends Controller
{
    public function __invoke(string $clientId, HydraClient $hydra): RedirectResponse
    {
        $user = Auth::user();

        try {
            $hydra->revokeConsentSession($user->hashid, $clientId);
        } catch (HydraRequestException $e) {
            return redirect()->back()->withErrors([
                'revoke' => trans('my_data_apps_error'),
            ]);
        }

        return redirect()->back()->with('success', trans('my_data_apps_revoked'));
    }
}
