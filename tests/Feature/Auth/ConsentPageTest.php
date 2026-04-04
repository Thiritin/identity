<?php

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeHydraConsentRequest(User $user, array $overrides = []): void
{
    $defaults = [
        'subject' => $user->hashid,
        'challenge' => 'test-challenge-123',
        'requested_scope' => ['openid', 'email', 'profile'],
        'requested_access_token_audience' => ['https://localhost/'],
        'client' => [
            'client_id' => 'external-app-id',
            'client_name' => 'External App',
        ],
    ];

    Http::fake([
        '*/admin/oauth2/auth/requests/consent?*' => Http::response(array_merge($defaults, $overrides)),
        '*/admin/oauth2/auth/requests/consent/accept*' => Http::response([
            'redirect_to' => 'https://app.example.com/callback',
        ]),
        '*/admin/oauth2/auth/requests/consent/reject*' => Http::response([
            'redirect_to' => 'https://app.example.com/callback?error=access_denied',
        ]),
    ]);
}

it('renders consent page for external apps', function () {
    $user = User::factory()->create();

    App::withoutEvents(fn () => App::factory()->create([
        'client_id' => 'external-app-id',
        'name' => 'Test External App',
        'description' => 'A test app',
        'developer_name' => 'Test Developer',
        'skip_consent' => false,
    ]));

    fakeHydraConsentRequest($user);

    $response = $this->get(route('auth.consent', ['consent_challenge' => 'test-challenge-123']));

    $response->assertInertia(fn ($page) => $page
        ->component('Auth/Consent')
        ->has('consentChallenge')
        ->where('app.name', 'Test External App')
        ->where('app.developerName', 'Test Developer')
        ->has('scopes', 2)
        ->where('scopes.0', 'email')
        ->where('scopes.1', 'profile')
    );
});

it('auto-accepts consent for apps with skip_consent', function () {
    $user = User::factory()->create();

    App::withoutEvents(fn () => App::factory()->skipConsent()->create([
        'client_id' => 'trusted-app-id',
    ]));

    fakeHydraConsentRequest($user, [
        'client' => ['client_id' => 'trusted-app-id', 'client_name' => 'Trusted App'],
    ]);

    $response = $this->get(route('auth.consent', ['consent_challenge' => 'test-challenge-123']));

    $response->assertRedirect('https://app.example.com/callback');
});

it('renders consent page with Hydra metadata when app not in database', function () {
    $user = User::factory()->create();

    fakeHydraConsentRequest($user, [
        'client' => [
            'client_id' => 'unknown-app-id',
            'client_name' => 'Hydra Registered App',
            'client_uri' => 'https://hydra-app.example.com',
            'policy_uri' => 'https://hydra-app.example.com/privacy',
            'tos_uri' => 'https://hydra-app.example.com/terms',
            'logo_uri' => 'https://hydra-app.example.com/logo.png',
        ],
    ]);

    $response = $this->get(route('auth.consent', ['consent_challenge' => 'test-challenge-123']));

    $response->assertInertia(fn ($page) => $page
        ->component('Auth/Consent')
        ->where('app.name', 'Hydra Registered App')
        ->where('app.privacyPolicyUrl', 'https://hydra-app.example.com/privacy')
        ->where('app.termsOfServiceUrl', 'https://hydra-app.example.com/terms')
        ->where('app.logoUri', 'https://hydra-app.example.com/logo.png')
    );
});

it('accepts consent and redirects', function () {
    $user = User::factory()->create();
    fakeHydraConsentRequest($user);

    $response = $this->post(route('auth.consent.accept'), [
        'consent_challenge' => 'test-challenge-123',
    ]);

    $response->assertRedirect('https://app.example.com/callback');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'consent/accept'));
});

it('denies consent and redirects with error', function () {
    $user = User::factory()->create();
    fakeHydraConsentRequest($user);

    $response = $this->post(route('auth.consent.deny'), [
        'consent_challenge' => 'test-challenge-123',
    ]);

    $response->assertRedirect('https://app.example.com/callback?error=access_denied');

    Http::assertSent(fn ($request) => str_contains($request->url(), 'consent/reject'));
});

it('rejects consent for suspended users', function () {
    $user = User::factory()->create(['suspended_at' => now()]);
    fakeHydraConsentRequest($user);

    $response = $this->get(route('auth.consent', ['consent_challenge' => 'test-challenge-123']));

    $response->assertRedirect('https://app.example.com/callback?error=access_denied');
});

it('rejects consent when user does not exist', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/consent/reject*' => Http::response([
            'redirect_to' => 'https://app.example.com/callback?error=login_required',
        ]),
        '*/admin/oauth2/auth/requests/consent?*' => Http::response([
            'subject' => 'nonexistent_user',
            'challenge' => 'test-challenge-123',
        ]),
    ]);

    $response = $this->get(route('auth.consent', ['consent_challenge' => 'test-challenge-123']));

    $response->assertRedirect('https://app.example.com/callback?error=login_required');
});
