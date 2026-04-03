<?php

use App\Services\Hydra\Client;
use App\Services\Hydra\HydraRequestException;
use Illuminate\Support\Facades\Http;

it('returns consent sessions for a subject', function () {
    Http::fake([
        '*/admin/oauth2/auth/sessions/consent*' => Http::response([
            [
                'consent_request' => [
                    'client' => ['client_id' => 'app-one'],
                ],
                'grant_scope' => ['openid', 'email'],
                'handled_at' => '2026-01-15T10:00:00Z',
            ],
            [
                'consent_request' => [
                    'client' => ['client_id' => 'app-two'],
                ],
                'grant_scope' => ['openid', 'profile'],
                'handled_at' => '2026-02-20T14:30:00Z',
            ],
        ]),
    ]);

    $client = new Client();
    $sessions = $client->getConsentSessions('user-hash-123');

    expect($sessions)->toHaveCount(2);
    expect($sessions[0]['consent_request']['client']['client_id'])->toBe('app-one');
    expect($sessions[1]['grant_scope'])->toBe(['openid', 'profile']);
});

it('throws HydraRequestException when consent sessions request fails', function () {
    Http::fake([
        '*/admin/oauth2/auth/sessions/consent*' => Http::response('Server Error', 500),
    ]);

    $client = new Client();
    $client->getConsentSessions('user-hash-123');
})->throws(HydraRequestException::class);

it('returns empty array when user has no consent sessions', function () {
    Http::fake([
        '*/admin/oauth2/auth/sessions/consent*' => Http::response([]),
    ]);

    $client = new Client();
    $sessions = $client->getConsentSessions('user-hash-123');

    expect($sessions)->toBe([]);
});

it('revokes a consent session for a specific client', function () {
    Http::fake([
        '*/admin/oauth2/auth/sessions/consent*' => Http::response(null, 204),
    ]);

    $client = new Client();
    $client->revokeConsentSession('user-hash-123', 'app-one');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'subject=user-hash-123')
            && str_contains($request->url(), 'client=app-one')
            && $request->method() === 'DELETE';
    });
});

it('throws HydraRequestException when revoking consent fails', function () {
    Http::fake([
        '*/admin/oauth2/auth/sessions/consent*' => Http::response('Server Error', 500),
    ]);

    $client = new Client();
    $client->revokeConsentSession('user-hash-123', 'app-one');
})->throws(HydraRequestException::class);

it('revokes all consent sessions for a subject', function () {
    Http::fake([
        '*/admin/oauth2/auth/sessions/consent*' => Http::response(null, 204),
    ]);

    $client = new Client();
    $client->revokeAllConsentSessions('user-hash-123');

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'subject=user-hash-123')
            && ! str_contains($request->url(), 'client=')
            && $request->method() === 'DELETE';
    });
});

it('throws HydraRequestException when revoking all consents fails', function () {
    Http::fake([
        '*/admin/oauth2/auth/sessions/consent*' => Http::response('Server Error', 500),
    ]);

    $client = new Client();
    $client->revokeAllConsentSessions('user-hash-123');
})->throws(HydraRequestException::class);
