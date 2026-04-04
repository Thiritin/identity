<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConsentRequest;
use App\Models\App;
use App\Models\OauthSession;
use App\Models\User;
use App\Services\Hydra\Client;
use App\Services\Hydra\HydraRequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class ConsentController extends Controller
{
    public function show(ConsentRequest $request)
    {
        try {
            $hydra = new Client();
            $consentRequest = $hydra->getConsentRequest($request->get('consent_challenge'));

            if (isset($consentRequest['redirect_to'])) {
                return redirect($consentRequest['redirect_to']);
            }

            $user = User::findByHashid($consentRequest['subject']);
            if ($user === null) {
                $response = $hydra->rejectConsentRequest($consentRequest, 'login_required', 'The account of the user does not exist.', 'Your user account does not exist anymore. It may have been deleted due to not being verified for over 24 hours.', 'The account of the user does not exist.', 401);

                return redirect($response['redirect_to']);
            }

            if ($user->isSuspended()) {
                $response = $hydra->rejectConsentRequest(
                    $consentRequest,
                    'access_denied',
                    'The user account has been suspended.',
                    trans('account_suspended'),
                    'User account is suspended.',
                    403
                );

                return redirect($response['redirect_to']);
            }

            $app = App::where('client_id', $consentRequest['client']['client_id'] ?? null)->first();

            $approvalRedirect = $this->rejectIfUnapproved($app, $user, $consentRequest, $hydra);
            if ($approvalRedirect) {
                return $approvalRedirect;
            }

            if ($app?->skip_consent) {
                $this->recordSession($consentRequest, $user);
                $response = $hydra->acceptConsentRequest($consentRequest, $user);

                return redirect($response['redirect_to']);
            }

            $client = $consentRequest['client'] ?? [];

            return Inertia::render('Auth/Consent', [
                'consentChallenge' => $consentRequest['challenge'],
                'app' => [
                    'name' => $app?->name ?? $client['client_name'] ?? 'Unknown App',
                    'icon' => $app?->icon,
                    'description' => $app?->description,
                    'url' => $app?->url ?? $client['client_uri'] ?? null,
                    'developerName' => $app?->developer_name,
                    'privacyPolicyUrl' => $app?->privacy_policy_url ?? $client['policy_uri'] ?? null,
                    'termsOfServiceUrl' => $app?->terms_of_service_url ?? $client['tos_uri'] ?? null,
                    'logoUri' => $client['logo_uri'] ?? null,
                ],
                'scopes' => collect($consentRequest['requested_scope'] ?? [])
                    ->reject(fn (string $scope) => $scope === 'openid')
                    ->values()
                    ->all(),
            ]);
        } catch (HydraRequestException $e) {
            return Redirect::route('auth.error', [
                'error' => 'consent_failed',
                'error_description' => 'The consent challenge is invalid or has expired.',
            ]);
        }
    }

    public function accept(ConsentRequest $request)
    {
        try {
            $hydra = new Client();
            $consentRequest = $hydra->getConsentRequest($request->get('consent_challenge'));

            if (isset($consentRequest['redirect_to'])) {
                return redirect($consentRequest['redirect_to']);
            }

            $user = User::findByHashid($consentRequest['subject']);
            if ($user === null) {
                $response = $hydra->rejectConsentRequest($consentRequest, 'login_required', 'The account of the user does not exist.', 'Your user account does not exist anymore.', 'The account of the user does not exist.', 401);

                return redirect($response['redirect_to']);
            }

            if ($user->isSuspended()) {
                $response = $hydra->rejectConsentRequest(
                    $consentRequest,
                    'access_denied',
                    'The user account has been suspended.',
                    trans('account_suspended'),
                    'User account is suspended.',
                    403
                );

                return redirect($response['redirect_to']);
            }

            $app = App::where('client_id', $consentRequest['client']['client_id'] ?? null)->first();

            $approvalRedirect = $this->rejectIfUnapproved($app, $user, $consentRequest, $hydra);
            if ($approvalRedirect) {
                return $approvalRedirect;
            }

            $this->recordSession($consentRequest, $user);
            $response = $hydra->acceptConsentRequest($consentRequest, $user);

            return redirect($response['redirect_to']);
        } catch (HydraRequestException $e) {
            return Redirect::route('auth.error', [
                'error' => 'consent_failed',
                'error_description' => 'The consent challenge is invalid or has expired.',
            ]);
        }
    }

    public function deny(ConsentRequest $request)
    {
        try {
            $hydra = new Client();
            $consentRequest = $hydra->getConsentRequest($request->get('consent_challenge'));

            if (isset($consentRequest['redirect_to'])) {
                return redirect($consentRequest['redirect_to']);
            }

            $response = $hydra->rejectConsentRequest(
                $consentRequest,
                'access_denied',
                'The user denied the consent request.',
                'You denied access to this application.',
                'User denied consent.',
                403
            );

            return redirect($response['redirect_to']);
        } catch (HydraRequestException $e) {
            return Redirect::route('auth.error', [
                'error' => 'consent_failed',
                'error_description' => 'The consent challenge is invalid or has expired.',
            ]);
        }
    }

    private function recordSession(array $consentRequest, User $user): void
    {
        $sessionId = $consentRequest['login_session_id'] ?? null;

        if ($sessionId === null) {
            return;
        }

        $session = OauthSession::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $user->id,
                'authenticated_at' => now(),
                'last_seen_at' => now(),
            ],
        );

        $session->update([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'last_seen_at' => now(),
        ]);

        $clientId = $consentRequest['client']['client_id'] ?? null;
        if ($clientId) {
            $session->addClientId($clientId);
        }

        Session::put('hydra_session_id', $sessionId);
    }

    private function rejectIfUnapproved(?App $app, User $user, array $consentRequest, Client $hydra): ?RedirectResponse
    {
        if ($app === null || $app->isApproved()) {
            return null;
        }

        if ($app->user_id === $user->id) {
            return null;
        }

        $response = $hydra->rejectConsentRequest(
            $consentRequest,
            'access_denied',
            'This application has not been approved for public use.',
            trans('app_not_approved'),
            'Application not approved for public use.',
            403
        );

        return redirect($response['redirect_to']);
    }
}
