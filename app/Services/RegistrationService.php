<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use RuntimeException;

class RegistrationService
{
    private const INACTIVE_STATUSES = ['cancelled', 'deleted'];

    public function hasActiveRegistration(): bool
    {
        $baseUrl = config('services.registration.attendee_service_url');

        if (empty($baseUrl)) {
            return false;
        }

        $token = Socialite::driver('idp-identity')->getToken();

        $response = Http::withToken($token)
            ->get($baseUrl . '/attendees');

        if ($response->failed()) {
            Log::error('Registration service listMyRegistrations failed', [
                'status' => $response->status(),
            ]);

            throw new RuntimeException(
                'Failed to check registration status.',
                $response->status(),
            );
        }

        $ids = $response->json('ids', []);

        if (empty($ids)) {
            return false;
        }

        foreach ($ids as $id) {
            $statusResponse = Http::withToken($token)
                ->get($baseUrl . '/attendees/' . $id . '/status');

            if ($statusResponse->failed()) {
                Log::error('Registration service getStatusById failed', [
                    'badge_number' => $id,
                    'status' => $statusResponse->status(),
                ]);

                throw new RuntimeException(
                    'Failed to check registration status.',
                    $statusResponse->status(),
                );
            }

            $status = $statusResponse->json('status');

            if (! in_array($status, self::INACTIVE_STATUSES, true)) {
                return true;
            }
        }

        return false;
    }
}
