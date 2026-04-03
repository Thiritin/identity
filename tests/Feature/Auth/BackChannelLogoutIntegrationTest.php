<?php

use App\Models\OauthSession;
use App\Models\User;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Traits\InteractsWithHydra;

uses(InteractsWithHydra::class);

// Use the main database (not idp-test) so the web server can see our test data.
// Hydra's backchannel logout calls hit the running web server which uses 'idp'.
beforeEach(function () {
    config(['database.connections.mysql.database' => 'idp']);
    DB::purge('mysql');
    DB::reconnect('mysql');
});

// Clean up after each test - we can't use RefreshDatabase because
// Hydra's backchannel logout call hits the web server (separate DB connection)
afterEach(function () {
    if (isset($this->testUser)) {
        OauthSession::where('user_id', $this->testUser->id)->delete();
        DB::table('sessions')->where('user_id', $this->testUser->id)->delete();
        $this->testUser->delete();
    }
    $this->deleteHydraClient();
});

it('processes backchannel logout when Hydra handles RP-initiated logout', function () {
    // 1. Create a real user
    $this->testUser = User::factory()->create(['remember_token' => 'test-token']);

    // 2. Create Hydra client with backchannel logout configured
    $client = $this->createHydraClient([
        'backchannel_logout_uri' => 'http://identity.eurofurence.localhost/auth/backchannel-logout',
        'backchannel_logout_session_required' => true,
        'scope' => 'openid',
        'post_logout_redirect_uris' => ['http://localhost:9999/logged-out'],
    ]);

    $adminUrl = rtrim(config('services.hydra.admin'), '/');

    // 3. Complete OAuth flow through Traefik with a Guzzle cookie jar so Hydra's
    // CSRF cookies are properly maintained through the redirect chain.
    $cookieJar = new CookieJar();
    $guzzle = new GuzzleClient(['cookies' => $cookieJar, 'allow_redirects' => false]);

    $authUrl = 'http://identity.eurofurence.localhost/oauth2/auth?' . http_build_query([
        'response_type' => 'code',
        'client_id' => $client['client_id'],
        'redirect_uri' => 'http://localhost:9999/callback',
        'scope' => 'openid',
        'state' => 'teststate-' . uniqid(),
    ]);

    // Initial auth request - Hydra sets CSRF cookie and redirects to login URL
    $authResponse = $guzzle->get($authUrl);
    $location = $authResponse->getHeaderLine('Location');
    parse_str(parse_url($location, PHP_URL_QUERY), $params);
    expect($params)->toHaveKey('login_challenge');
    $loginChallenge = $params['login_challenge'];

    // 4. Accept login via Hydra admin API
    $loginAccept = Http::put(
        $adminUrl . '/admin/oauth2/auth/requests/login/accept?challenge=' . $loginChallenge,
        [
            'subject' => $this->testUser->hashid,
            'remember' => true,
            'remember_for' => 3600,
        ]
    )->json();
    expect($loginAccept)->toHaveKey('redirect_to');

    // 5. Follow login_verifier redirect with cookies to get consent challenge
    $consentRedirect = $guzzle->get($loginAccept['redirect_to']);
    $consentLocation = $consentRedirect->getHeaderLine('Location');
    parse_str(parse_url($consentLocation, PHP_URL_QUERY), $consentParams);
    expect($consentParams)->toHaveKey('consent_challenge');
    $consentChallenge = $consentParams['consent_challenge'];

    // 6. Get consent request details to extract login_session_id (sid)
    $consentRequest = Http::get(
        $adminUrl . '/admin/oauth2/auth/requests/consent',
        ['challenge' => $consentChallenge]
    )->json();

    $sid = $consentRequest['login_session_id'];
    expect($sid)->not->toBeEmpty();

    // 7. Accept consent and follow redirect to complete the OAuth flow
    $consentAccept = Http::put(
        $adminUrl . '/admin/oauth2/auth/requests/consent/accept?challenge=' . $consentChallenge,
        [
            'grant_scope' => ['openid'],
            'grant_access_token_audience' => [],
            'handled_at' => now()->toISOString(),
        ]
    )->json();
    expect($consentAccept)->toHaveKey('redirect_to');

    // Follow consent_verifier redirect to get the authorization code
    $codeRedirect = $guzzle->get($consentAccept['redirect_to']);
    $codeLocation = $codeRedirect->getHeaderLine('Location');
    expect($codeLocation)->toContain('code=');

    // Exchange the authorization code for tokens to fully finalize the session
    parse_str(parse_url($codeLocation, PHP_URL_QUERY), $codeParams);
    $tokenResponse = Http::asForm()->post(
        'http://identity.eurofurence.localhost/oauth2/token',
        [
            'grant_type' => 'authorization_code',
            'code' => $codeParams['code'],
            'redirect_uri' => 'http://localhost:9999/callback',
            'client_id' => $client['client_id'],
            'client_secret' => $client['client_secret'],
        ]
    );
    expect($tokenResponse->successful())->toBeTrue();

    $tokens = $tokenResponse->json();
    $idToken = $tokens['id_token'];

    // 8. Create session records in the main database (shared with web server)
    OauthSession::create([
        'user_id' => $this->testUser->id,
        'session_id' => $sid,
        'client_ids' => [$client['client_id']],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Integration Test',
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);

    DB::table('sessions')->insert([
        'id' => 'integration-test-session-' . uniqid(),
        'user_id' => $this->testUser->id,
        'hydra_sid' => $sid,
        'payload' => base64_encode(serialize([])),
        'last_activity' => time(),
    ]);

    // Verify records exist before logout
    $this->assertDatabaseHas('oauth_sessions', ['session_id' => $sid]);
    $this->assertDatabaseHas('sessions', ['hydra_sid' => $sid]);

    // 9. Initiate RP-initiated logout via OIDC end_session endpoint.
    // This is the proper way to trigger backchannel logout in Hydra.
    $logoutUrl = 'http://identity.eurofurence.localhost/oauth2/sessions/logout?' . http_build_query([
        'id_token_hint' => $idToken,
        'post_logout_redirect_uri' => 'http://localhost:9999/logged-out',
        'state' => 'logout-test',
    ]);

    $logoutResponse = $guzzle->get($logoutUrl);
    $logoutLocation = $logoutResponse->getHeaderLine('Location');

    // Hydra redirects to our logout URL with a logout_challenge
    if (str_contains($logoutLocation, 'logout_challenge')) {
        parse_str(parse_url($logoutLocation, PHP_URL_QUERY), $logoutParams);
        $logoutChallenge = $logoutParams['logout_challenge'];

        // Accept the logout request via admin API
        $logoutAccept = Http::put(
            $adminUrl . '/admin/oauth2/auth/requests/logout/accept?challenge=' . $logoutChallenge
        )->json();

        // Follow the logout redirect to complete the flow and trigger backchannel logout
        if (isset($logoutAccept['redirect_to'])) {
            $guzzle->get($logoutAccept['redirect_to']);
        }
    } else {
        // Hydra might auto-accept the logout (skip_logout_consent)
        while ($logoutLocation && ! str_contains($logoutLocation, 'localhost:9999')) {
            $nextResponse = $guzzle->get($logoutLocation);
            $logoutLocation = $nextResponse->getHeaderLine('Location');
        }
    }

    // 10. Wait for Hydra to process the backchannel logout call
    sleep(3);

    // 11. Verify cleanup
    $this->assertDatabaseMissing('oauth_sessions', ['session_id' => $sid]);
    $this->assertDatabaseMissing('sessions', ['hydra_sid' => $sid]);
    $freshUser = $this->testUser->fresh();
    expect($freshUser)->not->toBeNull()
        ->and($freshUser->remember_token)->toBeNull();
});
