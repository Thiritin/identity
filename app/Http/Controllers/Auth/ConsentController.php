<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConsentRequest;
use App\Models\User;
use App\Services\Hydra\Client;

class ConsentController extends Controller
{
    public function __invoke(ConsentRequest $request)
    {
        $hydra = new Client();
        $consentRequest = $hydra->getConsentRequest($request->get('consent_challenge'));
        if (isset($consentRequest['redirect_to'])) {
            return redirect($consentRequest['redirect_to']);
        }
        $user = User::findByHashid($consentRequest['subject']);
        if ($user === null) {
            $response = $hydra->rejectConsentRequest($consentRequest, 'login_required', 'The account of the user does not exist.', 'Your user account does not exist anymore. It may have been deleted due to not being verified for over 24 hours.', 'The account of the user does not exist.', '401');

            return redirect($response['redirect_to']);
        }
        $response = $hydra->acceptConsentRequest($consentRequest, $user);

        return redirect($response['redirect_to']);
    }
}
