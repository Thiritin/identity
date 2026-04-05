<?php

namespace Tests\Feature\Developer;

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        if (str_contains($request->url(), '.well-known/openid-configuration')) {
            return Http::response(['scopes_supported' => ['openid', 'offline_access', 'profile', 'email']]);
        }
        $clientId = 'test-client-' . uniqid();
        return Http::response([
            'client_id' => $clientId,
            'client_secret' => 'test-raw-secret-' . uniqid(),
            'client_name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'scope' => 'openid',
            'token_endpoint_auth_method' => 'client_secret_post',
            'subject_type' => 'public',
            'post_logout_redirect_uris' => [],
        ]);
    });
});

// Already covered by AppsTest.php:
// - "developer user can create an app" → redirects (302) and creates app
// - "create app validation requires client_name and redirect_uri" → assertSessionHasErrors
// - "create app validation requires valid url in redirect_uri"
// - "developer cannot create app with restricted scopes"

test('create app redirects to credentials page', function () {
    $user = User::factory()->developer()->create();

    $response = $this->actingAs($user)->post(route('developers.store'), [
        'client_name' => 'My New App',
        'redirect_uri' => 'https://myapp.com/callback',
        'scope' => ['openid'],
    ]);

    $app = $user->apps()->first();
    $response->assertRedirect(route('developers.credentials', $app));
});

test('extra fields in create payload are silently ignored', function () {
    $user = User::factory()->developer()->create();

    $this->actingAs($user)->post(route('developers.store'), [
        'client_name' => 'My App',
        'redirect_uri' => 'https://myapp.com/callback',
        'description' => 'should be ignored',
        'app_url' => 'https://myapp.com',
        'first_party' => true,
    ])->assertRedirect();

    $app = $user->apps()->first();
    expect($app)->not->toBeNull();
    // Extra fields are ignored; the app is created successfully
    expect($app->name)->toBe('My App');
});

test('staff user gets first_party = true on created app', function () {
    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $staff = User::factory()->developer()->create();
    $staff->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    // Staff must have TOTP to access protected routes (EnsureStaffTwoFactor middleware)
    $staff->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $this->actingAs($staff)->post(route('developers.store'), [
        'client_name' => 'Staff App',
        'redirect_uri' => 'https://example.com/callback',
        'scope' => ['openid'],
    ])->assertRedirect();

    $app = $staff->apps()->first();
    expect($app)->not->toBeNull();
    expect($app->isFirstParty())->toBeTrue();
});

test('non-staff app gets first_party = false even if payload says first_party true', function () {
    $user = User::factory()->developer()->create();
    // Not in any staff group

    $this->actingAs($user)->post(route('developers.store'), [
        'client_name' => 'Evil First Party',
        'redirect_uri' => 'https://example.com/callback',
        'scope' => ['openid'],
        'first_party' => true,
    ])->assertRedirect();

    $app = $user->apps()->first();
    expect($app->isFirstParty())->toBeFalse();
});
