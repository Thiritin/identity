<?php

use App\Models\OauthSession;
use App\Models\User;
use App\Services\Hydra\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('invalidates a single Hydra session by sid', function () {
    Http::fake([
        '*/admin/oauth2/auth/sessions/login/*' => Http::response(null, 204),
    ]);

    $client = new Client();
    $result = $client->invalidateSession('test-session-uuid');

    expect($result)->toBeTrue();

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/admin/oauth2/auth/sessions/login/test-session-uuid')
            && $request->method() === 'DELETE';
    });
});

it('records an oauth session on consent flow', function () {
    $user = User::factory()->create();

    Http::fake([
        '*/admin/oauth2/auth/requests/consent/accept*' => Http::response([
            'redirect_to' => 'https://app.test/callback',
        ]),
        '*/admin/oauth2/auth/requests/consent*' => Http::response([
            'subject' => $user->hashid,
            'challenge' => 'consent-challenge-123',
            'login_session_id' => 'hydra-session-uuid-abc',
            'requested_scope' => ['openid'],
            'requested_access_token_audience' => ['https://app.test/'],
            'client' => ['client_id' => 'test-client-id'],
        ]),
    ]);

    $this->get(route('auth.consent', ['consent_challenge' => 'consent-challenge-123']));

    $this->assertDatabaseHas('oauth_sessions', [
        'user_id' => $user->id,
        'session_id' => 'hydra-session-uuid-abc',
        'last_client_id' => 'test-client-id',
    ]);
});

it('updates existing oauth session on repeat consent', function () {
    $user = User::factory()->create();

    $session = OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'hydra-session-uuid-abc',
        'ip_address' => '10.0.0.1',
        'user_agent' => 'OldBrowser/1.0',
        'last_client_id' => 'old-client',
        'authenticated_at' => now()->subDay(),
        'last_seen_at' => now()->subDay(),
    ]);

    $originalAuthenticatedAt = $session->authenticated_at->toISOString();

    Http::fake([
        '*/admin/oauth2/auth/requests/consent/accept*' => Http::response([
            'redirect_to' => 'https://app.test/callback',
        ]),
        '*/admin/oauth2/auth/requests/consent*' => Http::response([
            'subject' => $user->hashid,
            'challenge' => 'consent-challenge-456',
            'login_session_id' => 'hydra-session-uuid-abc',
            'requested_scope' => ['openid'],
            'requested_access_token_audience' => ['https://app.test/'],
            'client' => ['client_id' => 'new-client'],
        ]),
    ]);

    $this->get(route('auth.consent', ['consent_challenge' => 'consent-challenge-456']));

    expect(OauthSession::where('session_id', 'hydra-session-uuid-abc')->count())->toBe(1);

    $session->refresh();
    expect($session->last_client_id)->toBe('new-client');
    expect($session->authenticated_at->toISOString())->toBe($originalAuthenticatedAt);
});

it('does not record session when login_session_id is missing', function () {
    $user = User::factory()->create();

    Http::fake([
        '*/admin/oauth2/auth/requests/consent/accept*' => Http::response([
            'redirect_to' => 'https://app.test/callback',
        ]),
        '*/admin/oauth2/auth/requests/consent*' => Http::response([
            'subject' => $user->hashid,
            'challenge' => 'consent-challenge-789',
            'requested_scope' => ['openid'],
            'requested_access_token_audience' => ['https://app.test/'],
            'client' => ['client_id' => 'test-client'],
        ]),
    ]);

    $this->get(route('auth.consent', ['consent_challenge' => 'consent-challenge-789']));

    expect(OauthSession::count())->toBe(0);
});

it('does not record session on redirect_to early return', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/consent*' => Http::response([
            'redirect_to' => 'https://hydra.test/already-handled',
        ]),
    ]);

    $this->get(route('auth.consent', ['consent_challenge' => 'expired-challenge']));

    expect(OauthSession::count())->toBe(0);
});

it('deletes oauth session record on logout', function () {
    $user = User::factory()->create();

    $oauthSession = OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'logout-session-uuid',
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);

    Http::fake([
        '*/oauth2/sessions/logout*' => Http::response(null, 200),
    ]);

    $this->actingAs($user)
        ->withSession(['hydra_session_id' => 'logout-session-uuid'])
        ->get(route('auth.logout'));

    $this->assertDatabaseMissing('oauth_sessions', [
        'session_id' => 'logout-session-uuid',
    ]);
});

it('revokes a single session', function () {
    $user = User::factory()->create();

    $session = OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'revoke-me-uuid',
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);

    Http::fake([
        '*/admin/oauth2/auth/sessions/login/*' => Http::response(null, 204),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('settings.security.sessions.destroy', $session))
        ->assertRedirect();

    $this->assertDatabaseMissing('oauth_sessions', ['session_id' => 'revoke-me-uuid']);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/admin/oauth2/auth/sessions/login/revoke-me-uuid')
            && $request->method() === 'DELETE';
    });
});

it('cannot revoke another users session', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $session = OauthSession::create([
        'user_id' => $otherUser->id,
        'session_id' => 'other-user-session',
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('settings.security.sessions.destroy', $session))
        ->assertForbidden();

    $this->assertDatabaseHas('oauth_sessions', ['session_id' => 'other-user-session']);
});

it('revokes all other sessions except current', function () {
    $user = User::factory()->create();

    OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'current-session',
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);
    OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'other-session-1',
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);
    OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'other-session-2',
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);

    Http::fake([
        '*/admin/oauth2/auth/sessions/login/*' => Http::response(null, 204),
    ]);

    $this->actingAs($user)
        ->withSession(['hydra_session_id' => 'current-session', 'auth.password_confirmed_at' => now()->unix()])
        ->delete(route('settings.security.sessions.destroy-others'))
        ->assertRedirect();

    $this->assertDatabaseHas('oauth_sessions', ['session_id' => 'current-session']);
    $this->assertDatabaseMissing('oauth_sessions', ['session_id' => 'other-session-1']);
    $this->assertDatabaseMissing('oauth_sessions', ['session_id' => 'other-session-2']);
});
