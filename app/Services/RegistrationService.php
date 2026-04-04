<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class RegistrationService
{
    public function hasActiveRegistration(User $user): bool
    {
        $baseUrl = config('services.registration.attendee_service_url');

        if (empty($baseUrl)) {
            return false;
        }

        $token = config('services.registration.attendee_service_token');

        $response = Http::withToken($token)
            ->post($baseUrl . '/attendees/find', [
                'match_any' => [
                    ['email' => $user->email],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Registration service request failed', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException(
                'Failed to check registration status.',
                $response->status(),
            );
        }

        $attendees = $response->json('attendees', []);

        return count($attendees) > 0;
    }
}
