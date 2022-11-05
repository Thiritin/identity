<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConsentRequest;
use App\Models\User;
use App\Services\Client;
use Vinkla\Hashids\Facades\Hashids;

class ConsentController extends Controller
{
    public function __invoke(ConsentRequest $request)
    {
        $hydra = new Client();
        $consentRequest = $hydra->getConsentRequest($request->get('consent_challenge'));
        $user = User::where('id', '=', Hashids::decode($consentRequest->subject))->firstOrFail();
        $response = $hydra->acceptConsentRequest($request->get('consent_challenge'), $user);
        return redirect($response->redirect_to);
    }
}
