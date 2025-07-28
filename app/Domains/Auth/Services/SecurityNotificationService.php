<?php

namespace App\Domains\Auth\Services;

use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SecurityNotificationService
{
    public function notifyNewPasskeyAdded(User $user, string $passkeyName, string $userAgent = null, string $ipAddress = null): void
    {
        $data = [
            'user' => $user,
            'passkey_name' => $passkeyName,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'timestamp' => now(),
        ];

        // Log the event
        Log::info('New passkey added', [
            'user_id' => $user->id,
            'passkey_name' => $passkeyName,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // Send email notification (if user has email notifications enabled)
        if ($user->email_verified_at && $this->userWantsSecurityNotifications($user)) {
            // In a real implementation, you'd create a Mailable class
            // Mail::to($user)->send(new PasskeyAddedNotification($data));
        }

        // Record activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'passkey_name' => $passkeyName,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ])
            ->log('passkey_added');
    }

    public function notifyPasskeyUsed(User $user, string $passkeyName, string $userAgent = null, string $ipAddress = null): void
    {
        // Log the event
        Log::info('Passkey used for authentication', [
            'user_id' => $user->id,
            'passkey_name' => $passkeyName,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // Record activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'passkey_name' => $passkeyName,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ])
            ->log('passkey_authentication');
    }

    public function notifyPasskeyRemoved(User $user, string $passkeyName, string $userAgent = null, string $ipAddress = null): void
    {
        $data = [
            'user' => $user,
            'passkey_name' => $passkeyName,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'timestamp' => now(),
        ];

        // Log the event
        Log::info('Passkey removed', [
            'user_id' => $user->id,
            'passkey_name' => $passkeyName,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // Send email notification
        if ($user->email_verified_at && $this->userWantsSecurityNotifications($user)) {
            // Mail::to($user)->send(new PasskeyRemovedNotification($data));
        }

        // Record activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'passkey_name' => $passkeyName,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ])
            ->log('passkey_removed');
    }

    public function notifyPasswordlessLogin(User $user, string $method, string $userAgent = null, string $ipAddress = null): void
    {
        // Log the event
        Log::info('Passwordless login successful', [
            'user_id' => $user->id,
            'method' => $method,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // Record activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'method' => $method,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ])
            ->log('passwordless_login');
    }

    public function notifyConsentGranted(User $user, string $clientName, array $scopes, bool $remembered = false): void
    {
        // Log the event
        Log::info('User granted consent', [
            'user_id' => $user->id,
            'client_name' => $clientName,
            'scopes' => $scopes,
            'remembered' => $remembered,
        ]);

        // Record activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'client_name' => $clientName,
                'scopes' => $scopes,
                'remembered' => $remembered,
            ])
            ->log('consent_granted');
    }

    public function notifyConsentDenied(User $user, string $clientName, array $requestedScopes): void
    {
        // Log the event
        Log::info('User denied consent', [
            'user_id' => $user->id,
            'client_name' => $clientName,
            'requested_scopes' => $requestedScopes,
        ]);

        // Record activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'client_name' => $clientName,
                'requested_scopes' => $requestedScopes,
            ])
            ->log('consent_denied');
    }

    public function notifyUnusualActivity(User $user, string $activity, array $context = []): void
    {
        $data = [
            'user' => $user,
            'activity' => $activity,
            'context' => $context,
            'timestamp' => now(),
        ];

        // Log the event
        Log::warning('Unusual security activity detected', [
            'user_id' => $user->id,
            'activity' => $activity,
            'context' => $context,
        ]);

        // Send immediate email notification for unusual activity
        if ($user->email_verified_at) {
            // Mail::to($user)->send(new UnusualActivityNotification($data));
        }

        // Record activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties($context)
            ->log('unusual_activity_' . str_replace(' ', '_', strtolower($activity)));
    }

    private function userWantsSecurityNotifications(User $user): bool
    {
        // In a real implementation, you'd check user preferences
        // For now, assume all users want security notifications
        return true;
    }

    public function getSecurityEvents(User $user, int $limit = 50): array
    {
        $activities = activity()
            ->causedBy($user)
            ->where('log_name', 'default')
            ->whereIn('description', [
                'passkey_added',
                'passkey_removed',
                'passkey_authentication',
                'passwordless_login',
                'consent_granted',
                'consent_denied'
            ])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'event' => $activity->description,
                'description' => $this->getEventDescription($activity),
                'properties' => $activity->properties,
                'timestamp' => $activity->created_at,
                'ip_address' => $activity->properties['ip_address'] ?? null,
                'user_agent' => $activity->properties['user_agent'] ?? null,
            ];
        })->toArray();
    }

    private function getEventDescription($activity): string
    {
        $props = $activity->properties;
        
        switch ($activity->description) {
            case 'passkey_added':
                return "Added passkey: {$props['passkey_name']}";
            case 'passkey_removed':
                return "Removed passkey: {$props['passkey_name']}";
            case 'passkey_authentication':
                return "Signed in with passkey: {$props['passkey_name']}";
            case 'passwordless_login':
                return "Passwordless sign-in using {$props['method']}";
            case 'consent_granted':
                $scopeCount = count($props['scopes'] ?? []);
                return "Granted access to {$props['client_name']} ({$scopeCount} permissions)";
            case 'consent_denied':
                return "Denied access to {$props['client_name']}";
            default:
                return ucfirst(str_replace('_', ' ', $activity->description));
        }
    }
}