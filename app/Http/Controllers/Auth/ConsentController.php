<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConsentRequest;
use App\Models\User;
use App\Services\Hydra\Client;
use Vinkla\Hashids\Facades\Hashids;

class ConsentController extends Controller
{
    public function __invoke(ConsentRequest $request)
    {
        $hydra = new Client();
        $consentRequest = $hydra->getConsentRequest($request->get('consent_challenge'));
        if (isset($consentRequest['redirect_to'])) {
            return redirect($consentRequest['redirect_to']);
        }
        $user = User::where('id', '=', Hashids::connection('user')->decode($consentRequest["subject"]))->firstOrFail();
        $response = $hydra->acceptConsentRequest($consentRequest, $user);
        return redirect($response['redirect_to']);
    }
}
