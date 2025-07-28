<?php

namespace App\Domains\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConsentRequest;
use App\Domains\User\Models\User;
use App\Domains\Auth\Services\Client;
use App\Domains\Auth\Services\SecurityNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class ConsentController extends Controller
{
    public function view(Request $request)
    {
        $request->validate([
            'consent_challenge' => 'required|string'
        ]);

        $hydra = new Client();
        $consentRequest = $hydra->getConsentRequest($request->get('consent_challenge'));
        
        if (isset($consentRequest['redirect_to'])) {
            return redirect($consentRequest['redirect_to']);
        }

        $user = User::findByHashid($consentRequest['subject']);
        
        if ($user === null) {
            $response = $hydra->rejectConsentRequest(
                $consentRequest, 
                'login_required', 
                'The account of the user does not exist.', 
                'Your user account does not exist anymore. It may have been deleted due to not being verified for over 24 hours.', 
                'The account of the user does not exist.', 
                '401'
            );
            return redirect($response['redirect_to']);
        }

        // Check if consent was previously granted and should be skipped
        if (isset($consentRequest['skip']) && $consentRequest['skip'] === true) {
            $response = $hydra->acceptConsentRequest($consentRequest, $user);
            return redirect($response['redirect_to']);
        }

        // Format scopes for display
        $scopes = $this->formatScopes($consentRequest['requested_scope']);
        
        // Get client information
        $client = $consentRequest['client'];

        return Inertia::render('Auth/Consent', [
            'consent_challenge' => $request->get('consent_challenge'),
            'client' => [
                'name' => $client['client_name'] ?? 'Unknown Application',
                'client_id' => $client['client_id'],
                'logo_uri' => $client['logo_uri'] ?? null,
                'policy_uri' => $client['policy_uri'] ?? null,
                'tos_uri' => $client['tos_uri'] ?? null,
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->profile_photo_path
            ],
            'scopes' => $scopes,
            'requestedAudience' => $consentRequest['requested_access_token_audience'] ?? [],
            'hideUserInfo' => true
        ]);
    }

    public function submit(Request $request)
    {
        $request->validate([
            'consent_challenge' => 'required|string',
            'action' => 'required|in:allow,deny',
            'remember' => 'boolean',
            'granted_scopes' => 'array'
        ]);

        $hydra = new Client();
        $consentRequest = $hydra->getConsentRequest($request->get('consent_challenge'));
        
        if (isset($consentRequest['redirect_to'])) {
            return redirect($consentRequest['redirect_to']);
        }

        $user = User::findByHashid($consentRequest['subject']);
        
        if ($user === null) {
            $response = $hydra->rejectConsentRequest(
                $consentRequest, 
                'login_required', 
                'The account of the user does not exist.', 
                'Your user account does not exist anymore.', 
                'The account of the user does not exist.', 
                '401'
            );
            return redirect($response['redirect_to']);
        }

        if ($request->get('action') === 'deny') {
            // Notify of consent denial
            app(SecurityNotificationService::class)->notifyConsentDenied(
                $user,
                $consentRequest['client']['client_name'] ?? 'Unknown Application',
                $consentRequest['requested_scope']
            );

            $response = $hydra->rejectConsentRequest(
                $consentRequest,
                'access_denied',
                'User denied the request',
                'The user denied the consent request.',
                'User clicked deny',
                '403'
            );
            return redirect($response['redirect_to']);
        }

        // Allow consent with selected scopes
        $grantedScopes = $request->get('granted_scopes', $consentRequest['requested_scope']);
        $remember = $request->get('remember', false);

        // Override consent request with user's choices
        $modifiedConsentRequest = $consentRequest;
        $modifiedConsentRequest['requested_scope'] = $grantedScopes;
        $modifiedConsentRequest['remember'] = $remember;
        $modifiedConsentRequest['remember_for'] = $remember ? 86400 * 30 : 0; // 30 days or 0

        $response = $hydra->acceptConsentRequest($modifiedConsentRequest, $user);
        
        // Notify of consent granted
        app(SecurityNotificationService::class)->notifyConsentGranted(
            $user,
            $consentRequest['client']['client_name'] ?? 'Unknown Application',
            $grantedScopes,
            $remember
        );
        
        return redirect($response['redirect_to']);
    }

    private function formatScopes(array $requestedScopes): array
    {
        $scopeDescriptions = [
            'openid' => [
                'name' => 'Basic Identity',
                'description' => 'Access your basic identity information',
                'icon' => 'user',
                'required' => true
            ],
            'email' => [
                'name' => 'Email Address',
                'description' => 'Access your email address',
                'icon' => 'mail',
                'required' => false
            ],
            'groups' => [
                'name' => 'Group Memberships',
                'description' => 'Access your group and organization memberships',
                'icon' => 'users',
                'required' => false
            ]
        ];

        $formattedScopes = [];
        
        foreach ($requestedScopes as $scope) {
            if (isset($scopeDescriptions[$scope])) {
                $formattedScopes[] = $scopeDescriptions[$scope];
            }
        }

        return $formattedScopes;
    }
}