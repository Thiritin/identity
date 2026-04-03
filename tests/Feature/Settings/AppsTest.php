<?php

namespace Tests\Feature\Settings;

use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function createDeveloperUser(): User
{
    return User::factory()->developer()->create();
}

function fakeHydra(): void
{
    Http::fake([
        '*/admin/clients' => Http::response([
            'client_id' => 'test-client-id-' . uniqid(),
            'client_secret' => 'test-raw-secret-' . uniqid(),
            'client_name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'scope' => 'openid',
            'token_endpoint_auth_method' => 'client_secret_post',
            'subject_type' => 'public',
            'post_logout_redirect_uris' => [],
        ]),
        '*/admin/clients/*' => Http::response([
            'client_id' => 'test-client-id',
            'client_name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'scope' => 'openid',
            'token_endpoint_auth_method' => 'client_secret_post',
            'subject_type' => 'public',
            'post_logout_redirect_uris' => [],
        ]),
        '*/.well-known/openid-configuration' => Http::response([
            'scopes_supported' => ['openid', 'offline_access', 'profile', 'email'],
        ]),
    ]);
}

test('non-developer user cannot access apps', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.apps.index'))
        ->assertForbidden();
});

test('developer user can list their own apps', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $app = App::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('settings.apps.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppsIndex', false)
            ->has('apps', 1)
        );
});

test('developer user cannot see other users apps', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $otherUser = User::factory()->create();
    App::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->get(route('settings.apps.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppsIndex', false)
            ->has('apps', 0)
        );
});

test('developer user can view create form', function () {
    fakeHydra();
    $user = createDeveloperUser();

    $this->actingAs($user)
        ->get(route('settings.apps.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppCreate', false)
            ->has('availableScopes')
        );
});

test('developer user can create an app', function () {
    fakeHydra();
    $user = createDeveloperUser();

    $response = $this->actingAs($user)
        ->post(route('settings.apps.store'), [
            'client_name' => 'My New App',
            'redirect_uris' => ['https://myapp.com/callback'],
            'post_logout_redirect_uris' => ['https://myapp.com/logout'],
            'scope' => ['openid', 'profile'],
        ]);

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppShow', false)
            ->has('clientSecret')
        );

    $app = $user->apps()->first();
    expect($app)->not->toBeNull();
    expect($app->name)->toBe('My New App');
});

test('developer user can view their own app', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $app = App::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('settings.apps.show', $app))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppShow', false)
            ->has('app')
        );
});

test('developer user cannot view another users app', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $otherApp = App::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.apps.show', $otherApp))
        ->assertForbidden();
});

test('developer user can edit their own app', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $app = App::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('settings.apps.edit', $app))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppEdit', false)
            ->has('app')
            ->has('availableScopes')
        );
});

test('developer user cannot edit another users app', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $otherApp = App::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.apps.edit', $otherApp))
        ->assertForbidden();
});

test('developer user can update their own app', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $app = App::factory()->create(['user_id' => $user->id, 'client_id' => 'test-client-id']);

    $this->actingAs($user)
        ->put(route('settings.apps.update', $app), [
            'client_name' => 'Updated Name',
            'redirect_uris' => ['https://updated.com/callback'],
            'scope' => ['openid', 'email'],
        ])
        ->assertRedirect();

    $app->refresh();
    expect($app->name)->toBe('Updated Name');
});

test('developer user cannot update another users app', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $otherApp = App::factory()->create();

    $this->actingAs($user)
        ->put(route('settings.apps.update', $otherApp), [
            'client_name' => 'Hacked',
            'redirect_uris' => ['https://evil.com/callback'],
        ])
        ->assertForbidden();
});

test('developer user can delete their own app', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $app = App::factory()->create(['user_id' => $user->id, 'client_id' => 'test-client-id']);

    $this->actingAs($user)
        ->delete(route('settings.apps.destroy', $app))
        ->assertRedirect(route('settings.apps.index'));

    expect(App::find($app->id))->toBeNull();
});

test('developer user cannot delete another users app', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $otherApp = App::factory()->create();

    $this->actingAs($user)
        ->delete(route('settings.apps.destroy', $otherApp))
        ->assertForbidden();
});

test('developer user can regenerate secret for their own app', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $app = App::factory()->create(['user_id' => $user->id, 'client_id' => 'test-client-id']);

    $this->actingAs($user)
        ->post(route('settings.apps.regenerate-secret', $app))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Apps/AppEdit', false)
            ->has('clientSecret')
        );

    $app->refresh();
    expect($app->client_secret)->not->toBeNull();
});

test('developer user cannot regenerate secret for another users app', function () {
    fakeHydra();
    $user = createDeveloperUser();
    $otherApp = App::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.apps.regenerate-secret', $otherApp))
        ->assertForbidden();
});

test('create app validation requires client_name and redirect_uris', function () {
    fakeHydra();
    $user = createDeveloperUser();

    $this->actingAs($user)
        ->post(route('settings.apps.store'), [])
        ->assertSessionHasErrors(['client_name', 'redirect_uris']);
});

test('create app validation requires valid urls in redirect_uris', function () {
    fakeHydra();
    $user = createDeveloperUser();

    $this->actingAs($user)
        ->post(route('settings.apps.store'), [
            'client_name' => 'Test',
            'redirect_uris' => ['not-a-url'],
        ])
        ->assertSessionHasErrors(['redirect_uris.0']);
});
