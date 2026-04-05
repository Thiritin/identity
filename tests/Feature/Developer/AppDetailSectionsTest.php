<?php

namespace Tests\Feature\Developer;

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        if (str_contains($request->url(), '.well-known/openid-configuration')) {
            return Http::response(['scopes_supported' => ['openid', 'offline_access', 'profile', 'email']]);
        }
        // Extract client_id from URL for existing client operations
        if (preg_match('#/admin/clients/([^/?]+)#', $request->url(), $m)) {
            $clientId = $m[1];
            // For PUT requests, echo back the request body with the client_id
            if ($request->method() === 'PUT') {
                $body = $request->data();
                $body['client_id'] = $clientId;
                return Http::response($body);
            }
        } else {
            $clientId = 'test-client-' . uniqid();
        }
        return Http::response([
            'client_id' => $clientId,
            'client_secret' => 'test-raw-secret',
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
// - general section (200 + Inertia component, owner can view, non-owner gets 403)
// - general.update (redirects, persists client_name)
// - oauth.update (restricted scopes → 422)

test('oauth section renders expected Inertia component for owner', function () {
    $user = User::factory()->developer()->create();
    $app = App::factory()->for($user, 'owner')->thirdParty()->create();

    $this->actingAs($user)
        ->get(route('developers.oauth', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppDetail/OAuth', false)
            ->has('app')
            ->has('availableScopes')
        );
});

test('oauth section returns 403 for non-owner', function () {
    $user = User::factory()->developer()->create();
    $otherApp = App::factory()->thirdParty()->create();

    $this->actingAs($user)
        ->get(route('developers.oauth', $otherApp))
        ->assertForbidden();
});

test('logout section renders expected Inertia component for owner', function () {
    $user = User::factory()->developer()->create();
    $app = App::factory()->for($user, 'owner')->thirdParty()->create();

    $this->actingAs($user)
        ->get(route('developers.logout', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppDetail/Logout', false)
            ->has('app')
        );
});

test('logout section returns 403 for non-owner', function () {
    $user = User::factory()->developer()->create();
    $otherApp = App::factory()->thirdParty()->create();

    $this->actingAs($user)
        ->get(route('developers.logout', $otherApp))
        ->assertForbidden();
});

test('credentials section renders expected Inertia component for owner', function () {
    $user = User::factory()->developer()->create();
    $app = App::factory()->for($user, 'owner')->thirdParty()->create();

    $this->actingAs($user)
        ->get(route('developers.credentials', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppDetail/Credentials', false)
            ->has('app')
        );
});

test('credentials section returns 403 for non-owner', function () {
    $user = User::factory()->developer()->create();
    $otherApp = App::factory()->thirdParty()->create();

    $this->actingAs($user)
        ->get(route('developers.credentials', $otherApp))
        ->assertForbidden();
});

test('danger section renders expected Inertia component for owner', function () {
    $user = User::factory()->developer()->create();
    $app = App::factory()->for($user, 'owner')->thirdParty()->create();

    $this->actingAs($user)
        ->get(route('developers.danger', $app))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppDetail/Danger', false)
            ->has('app')
        );
});

test('danger section returns 403 for non-owner', function () {
    $user = User::factory()->developer()->create();
    $otherApp = App::factory()->thirdParty()->create();

    $this->actingAs($user)
        ->get(route('developers.danger', $otherApp))
        ->assertForbidden();
});

test('general update persists name, description, and app URL', function () {
    $user = User::factory()->developer()->create();
    $app = App::factory()->for($user, 'owner')->thirdParty()->create();

    $this->actingAs($user)->put(route('developers.general.update', $app), [
        'client_name' => 'Updated Name',
        'description' => 'A great app',
        'app_url' => 'https://updated.example.com',
        'developer_name' => 'Dev Corp',
        'privacy_policy_url' => 'https://updated.example.com/privacy',
        'terms_of_service_url' => 'https://updated.example.com/terms',
    ])->assertRedirect(route('developers.general', $app));

    $app->refresh();
    expect($app->name)->toBe('Updated Name');
    expect($app->description)->toBe('A great app');
    expect($app->url)->toBe('https://updated.example.com');
});

test('oauth update persists redirect URIs and scope', function () {
    $user = User::factory()->developer()->create();
    $app = App::factory()->for($user, 'owner')->thirdParty()->create();

    $this->actingAs($user)->put(route('developers.oauth.update', $app), [
        'redirect_uris' => ['https://myapp.example.com/callback', 'https://myapp.example.com/callback2'],
        'scope' => ['openid', 'profile'],
    ])->assertRedirect(route('developers.oauth', $app));

    $app->refresh();
    expect($app->data['redirect_uris'])->toBe(['https://myapp.example.com/callback', 'https://myapp.example.com/callback2']);
    // scope may come back as a space-delimited string from Hydra; normalise for assertion
    $scope = $app->data['scope'];
    if (is_string($scope)) {
        $scope = explode(' ', $scope);
    }
    expect($scope)->toContain('openid')->toContain('profile');
});

test('logout update persists post-logout URIs and logout channel URIs', function () {
    $user = User::factory()->developer()->create();
    $app = App::factory()->for($user, 'owner')->thirdParty()->create();

    $this->actingAs($user)->put(route('developers.logout.update', $app), [
        'post_logout_redirect_uris' => ['https://myapp.example.com/logged-out'],
        'frontchannel_logout_uri' => 'https://myapp.example.com/frontchannel-logout',
        'backchannel_logout_uri' => 'https://myapp.example.com/backchannel-logout',
    ])->assertRedirect(route('developers.logout', $app));

    $app->refresh();
    expect($app->data['post_logout_redirect_uris'])->toBe(['https://myapp.example.com/logged-out']);
    expect($app->data['frontchannel_logout_uri'])->toBe('https://myapp.example.com/frontchannel-logout');
    expect($app->data['backchannel_logout_uri'])->toBe('https://myapp.example.com/backchannel-logout');
});

test('oauth update does not touch general fields', function () {
    $user = User::factory()->developer()->create();
    $app = App::factory()->for($user, 'owner')->thirdParty()->create([
        'name' => 'Original Name',
        'description' => 'Original description',
    ]);

    $this->actingAs($user)->put(route('developers.oauth.update', $app), [
        'redirect_uris' => ['https://example.com/callback'],
        'scope' => ['openid'],
    ])->assertRedirect();

    $app->refresh();
    // General fields are unchanged by the OAuth update
    expect($app->name)->toBe('Original Name');
    expect($app->description)->toBe('Original description');
});
