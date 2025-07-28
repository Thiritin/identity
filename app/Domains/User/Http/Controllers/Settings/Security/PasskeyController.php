<?php

namespace App\Domains\User\Http\Controllers\Settings\Security;

use App\Http\Controllers\Controller;
use App\Domains\Auth\Services\SecurityNotificationService;
use App\Domains\Auth\Services\WebAuthn\WebAuthnService;
use App\Domains\User\Models\WebauthnCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PasskeyController extends Controller
{
    private WebAuthnService $webauthnService;
    private SecurityNotificationService $securityNotificationService;

    public function __construct(
        WebAuthnService $webauthnService,
        SecurityNotificationService $securityNotificationService
    ) {
        $this->webauthnService = $webauthnService;
        $this->securityNotificationService = $securityNotificationService;
    }

    public function index()
    {
        $user = Auth::user();
        
        $credentials = $user->webauthnCredentials()
            ->orderBy('last_used_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($credential) {
                return [
                    'id' => $credential->id,
                    'name' => $credential->device_name,
                    'created_at' => $credential->created_at->format('M j, Y'),
                    'last_used_at' => $credential->last_used_human,
                    'sign_count' => $credential->sign_count,
                    'transports' => $credential->transport_types,
                ];
            });

        return Inertia::render('User/Security/Passkeys', [
            'credentials' => $credentials,
            'hasCredentials' => $credentials->isNotEmpty()
        ]);
    }

    public function register(Request $request)
    {
        $user = Auth::user();
        
        // Generate registration options
        $options = $this->webauthnService->generateRegistrationOptions($user);
        
        return response()->json([
            'challenge' => base64_encode($options->getChallenge()),
            'rp' => [
                'name' => $options->getRp()->getName(),
                'id' => $options->getRp()->getId()
            ],
            'user' => [
                'id' => base64_encode($options->getUser()->getId()),
                'name' => $options->getUser()->getName(),
                'displayName' => $options->getUser()->getDisplayName()
            ],
            'pubKeyCredParams' => array_map(function ($param) {
                return [
                    'type' => $param->getType(),
                    'alg' => $param->getAlg()
                ];
            }, $options->getPubKeyCredParams()),
            'excludeCredentials' => array_map(function ($cred) {
                return [
                    'type' => $cred->getType(),
                    'id' => base64_encode($cred->getId()),
                    'transports' => $cred->getTransports()
                ];
            }, $options->getExcludeCredentials()),
            'authenticatorSelection' => [
                'requireResidentKey' => $options->getAuthenticatorSelection()?->getRequireResidentKey(),
                'userVerification' => $options->getAuthenticatorSelection()?->getUserVerification()
            ],
            'timeout' => $options->getTimeout()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'credential' => 'required|array',
            'credential.id' => 'required|string',
            'credential.rawId' => 'required|string',
            'credential.response' => 'required|array',
            'credential.response.attestationObject' => 'required|string',
            'credential.response.clientDataJSON' => 'required|string',
        ]);

        $user = Auth::user();
        
        // Get the stored registration options from session/cache
        $challengeKey = $this->webauthnService->getChallengeKey($user, 'registration');
        $storedChallenge = \Cache::get($challengeKey);
        
        if (!$storedChallenge) {
            throw ValidationException::withMessages([
                'credential' => 'Registration session expired. Please try again.'
            ]);
        }

        // Create a mock options object for verification
        $options = $this->webauthnService->generateRegistrationOptions($user);
        
        // Prepare credential data for verification
        $credentialData = $request->get('credential');
        $credentialData['name'] = $request->get('name');
        $credentialData['aaguid'] = $credentialData['aaguid'] ?? null;
        $credentialData['transports'] = $credentialData['transports'] ?? [];

        $credential = $this->webauthnService->verifyRegistration(
            $user,
            $credentialData,
            $options
        );

        if (!$credential) {
            throw ValidationException::withMessages([
                'credential' => 'Failed to register security key. Please try again.'
            ]);
        }

        // Send security notification
        $this->securityNotificationService->notifyNewPasskeyAdded(
            $user,
            $credential->device_name,
            $request->userAgent(),
            $request->ip()
        );

        return response()->json([
            'message' => 'Security key registered successfully',
            'credential' => [
                'id' => $credential->id,
                'name' => $credential->device_name,
                'created_at' => $credential->created_at->format('M j, Y')
            ]
        ]);
    }

    public function update(Request $request, WebauthnCredential $credential)
    {
        // Ensure the credential belongs to the authenticated user
        if ($credential->user_id !== Auth::id()) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $credential->update([
            'name' => $request->get('name')
        ]);

        return response()->json([
            'message' => 'Security key name updated successfully',
            'credential' => [
                'id' => $credential->id,
                'name' => $credential->device_name
            ]
        ]);
    }

    public function destroy(WebauthnCredential $credential)
    {
        // Ensure the credential belongs to the authenticated user
        if ($credential->user_id !== Auth::id()) {
            abort(404);
        }

        $user = Auth::user();
        
        // Don't allow deletion if it's the only authentication method
        if (!$user->password && $user->webauthnCredentials()->count() === 1) {
            throw ValidationException::withMessages([
                'credential' => 'Cannot remove the only authentication method. Please set a password first.'
            ]);
        }

        $credentialName = $credential->device_name;
        $credential->delete();

        // Send security notification
        $this->securityNotificationService->notifyPasskeyRemoved(
            $user,
            $credentialName,
            request()->userAgent(),
            request()->ip()
        );

        return response()->json([
            'message' => 'Security key removed successfully'
        ]);
    }
}