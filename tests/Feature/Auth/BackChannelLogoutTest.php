<?php

use App\Models\OauthSession;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function generateLogoutToken(array $claims = [], $key = null, string $alg = 'RS256'): string
{
    if ($key === null) {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    }

    $defaults = [
        'iss' => config('services.hydra.public'),
        'aud' => config('services.apps.portal.client_id'),
        'iat' => time(),
        'jti' => Str::uuid()->toString(),
        'events' => ['http://schemas.openid.net/event/backchannel-logout' => (object) []],
        'sid' => 'hydra-session-123',
    ];

    $payload = array_merge($defaults, $claims);

    return JWT::encode($payload, $key, $alg, 'test-key-id');
}

function cacheTestJwks($privateKey = null): OpenSSLAsymmetricKey
{
    if ($privateKey === null) {
        $privateKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    }

    $details = openssl_pkey_get_details($privateKey);

    $jwks = [
        'keys' => [[
            'kty' => 'RSA',
            'kid' => 'test-key-id',
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '='),
        ]],
    ];

    Cache::put('hydra_jwks', $jwks, now()->addHour());

    return $privateKey;
}

it('processes a valid backchannel logout token', function () {
    $user = User::factory()->create(['remember_token' => 'old-token']);
    $privateKey = cacheTestJwks();

    OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'hydra-session-123',
        'client_ids' => ['portal-client'],
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);

    DB::table('sessions')->insert([
        'id' => 'laravel-session-abc',
        'user_id' => $user->id,
        'hydra_sid' => 'hydra-session-123',
        'payload' => base64_encode('{}'),
        'last_activity' => time(),
    ]);

    $token = generateLogoutToken(['sid' => 'hydra-session-123'], $privateKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertOk();

    $this->assertDatabaseMissing('sessions', ['hydra_sid' => 'hydra-session-123']);
    $this->assertDatabaseMissing('oauth_sessions', ['session_id' => 'hydra-session-123']);
    expect($user->fresh()->remember_token)->toBeNull();
});

it('returns 200 even when no matching session exists (idempotent)', function () {
    $privateKey = cacheTestJwks();
    $token = generateLogoutToken(['sid' => 'nonexistent-session'], $privateKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertOk();
});

it('rejects a logout token without logout_token parameter', function () {
    $this->post('/auth/backchannel-logout', [])
        ->assertStatus(400);
});

it('rejects a logout token with invalid JWT signature', function () {
    cacheTestJwks();
    $differentKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $token = generateLogoutToken([], $differentKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertStatus(400);
});

it('rejects a logout token with wrong issuer', function () {
    $privateKey = cacheTestJwks();
    $token = generateLogoutToken(['iss' => 'https://evil.example.com'], $privateKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertStatus(400);
});

it('rejects a logout token with wrong audience', function () {
    $privateKey = cacheTestJwks();
    $token = generateLogoutToken(['aud' => 'wrong-client-id'], $privateKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertStatus(400);
});

it('rejects a logout token missing events claim', function () {
    $privateKey = cacheTestJwks();
    $token = generateLogoutToken(['events' => null], $privateKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertStatus(400);
});

it('rejects a logout token with nonce claim', function () {
    $privateKey = cacheTestJwks();
    $token = generateLogoutToken(['nonce' => 'some-nonce'], $privateKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertStatus(400);
});

it('rejects a logout token with stale iat', function () {
    $privateKey = cacheTestJwks();
    $token = generateLogoutToken(['iat' => time() - 600], $privateKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertStatus(400);
});

it('rejects a logout token missing sid claim', function () {
    $privateKey = cacheTestJwks();

    $payload = [
        'iss' => config('services.hydra.public'),
        'aud' => config('services.apps.portal.client_id'),
        'iat' => time(),
        'jti' => Str::uuid()->toString(),
        'events' => ['http://schemas.openid.net/event/backchannel-logout' => (object) []],
    ];
    $token = JWT::encode($payload, $privateKey, 'RS256', 'test-key-id');

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertStatus(400);
});

it('rejects a replayed logout token (duplicate jti)', function () {
    $privateKey = cacheTestJwks();
    $jti = 'unique-jti-value';
    $token = generateLogoutToken(['jti' => $jti], $privateKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertOk();

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertStatus(400);
});

it('rejects when sub does not match session owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $privateKey = cacheTestJwks();

    OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'hydra-session-sub-check',
        'client_ids' => ['portal-client'],
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);

    $token = generateLogoutToken([
        'sid' => 'hydra-session-sub-check',
        'sub' => $otherUser->hashid,
    ], $privateKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertStatus(400);

    $this->assertDatabaseHas('oauth_sessions', ['session_id' => 'hydra-session-sub-check']);
});

it('deletes multiple Laravel sessions matching the same hydra_sid', function () {
    $user = User::factory()->create(['remember_token' => 'token']);
    $privateKey = cacheTestJwks();

    OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'multi-session-sid',
        'client_ids' => ['portal', 'staff'],
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);

    DB::table('sessions')->insert([
        ['id' => 'laravel-1', 'user_id' => $user->id, 'hydra_sid' => 'multi-session-sid', 'payload' => base64_encode('{}'), 'last_activity' => time()],
        ['id' => 'laravel-2', 'user_id' => $user->id, 'hydra_sid' => 'multi-session-sid', 'payload' => base64_encode('{}'), 'last_activity' => time()],
    ]);

    $token = generateLogoutToken(['sid' => 'multi-session-sid'], $privateKey);

    $this->post('/auth/backchannel-logout', ['logout_token' => $token])
        ->assertOk();

    expect(DB::table('sessions')->where('hydra_sid', 'multi-session-sid')->count())->toBe(0);
    expect($user->fresh()->remember_token)->toBeNull();
});
