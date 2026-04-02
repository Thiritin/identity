<?php

namespace Tests\Feature\Staff;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\App;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function createStaffUser(): User
{
    $user = User::factory()->create();
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
        'slug' => 'staff',
    ]);
    $staffGroup->users()->attach($user->id, ['level' => GroupUserLevel::Member]);

    return $user;
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

test('staff user can list their own apps', function () {
    fakeHydra();
    $user = createStaffUser();
    $app = App::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'staff')
        ->get(route('staff.apps.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Staff/Apps/AppsIndex', false)
            ->has('apps', 1)
        );
});

test('staff user cannot see other users apps', function () {
    fakeHydra();
    $user = createStaffUser();
    $otherUser = User::factory()->create();
    App::factory()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user, 'staff')
        ->get(route('staff.apps.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Staff/Apps/AppsIndex', false)
            ->has('apps', 0)
        );
});

test('staff user can view create form', function () {
    fakeHydra();
    $user = createStaffUser();

    $this->actingAs($user, 'staff')
        ->get(route('staff.apps.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Staff/Apps/AppCreate', false)
            ->has('availableScopes')
        );
});

test('staff user can create an app', function () {
    fakeHydra();
    $user = createStaffUser();

    $response = $this->actingAs($user, 'staff')
        ->post(route('staff.apps.store'), [
            'client_name' => 'My New App',
            'redirect_uris' => ['https://myapp.com/callback'],
            'post_logout_redirect_uris' => ['https://myapp.com/logout'],
            'scope' => ['openid', 'profile'],
        ]);

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Staff/Apps/AppShow', false)
            ->has('clientSecret')
        );

    $app = $user->apps()->first();
    expect($app)->not->toBeNull();
    expect($app->name)->toBe('My New App');
});

test('staff user can view their own app', function () {
    fakeHydra();
    $user = createStaffUser();
    $app = App::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'staff')
        ->get(route('staff.apps.show', $app))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Staff/Apps/AppShow', false)
            ->has('app')
        );
});

test('staff user cannot view another users app', function () {
    fakeHydra();
    $user = createStaffUser();
    $otherApp = App::factory()->create();

    $this->actingAs($user, 'staff')
        ->get(route('staff.apps.show', $otherApp))
        ->assertForbidden();
});

test('staff user can edit their own app', function () {
    fakeHydra();
    $user = createStaffUser();
    $app = App::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'staff')
        ->get(route('staff.apps.edit', $app))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Staff/Apps/AppEdit', false)
            ->has('app')
            ->has('availableScopes')
        );
});

test('staff user cannot edit another users app', function () {
    fakeHydra();
    $user = createStaffUser();
    $otherApp = App::factory()->create();

    $this->actingAs($user, 'staff')
        ->get(route('staff.apps.edit', $otherApp))
        ->assertForbidden();
});

test('staff user can update their own app', function () {
    fakeHydra();
    $user = createStaffUser();
    $app = App::factory()->create(['user_id' => $user->id, 'client_id' => 'test-client-id']);

    $this->actingAs($user, 'staff')
        ->put(route('staff.apps.update', $app), [
            'client_name' => 'Updated Name',
            'redirect_uris' => ['https://updated.com/callback'],
            'scope' => ['openid', 'email'],
        ])
        ->assertRedirect();

    $app->refresh();
    expect($app->name)->toBe('Updated Name');
});

test('staff user cannot update another users app', function () {
    fakeHydra();
    $user = createStaffUser();
    $otherApp = App::factory()->create();

    $this->actingAs($user, 'staff')
        ->put(route('staff.apps.update', $otherApp), [
            'client_name' => 'Hacked',
            'redirect_uris' => ['https://evil.com/callback'],
        ])
        ->assertForbidden();
});

test('staff user can delete their own app', function () {
    fakeHydra();
    $user = createStaffUser();
    $app = App::factory()->create(['user_id' => $user->id, 'client_id' => 'test-client-id']);

    $this->actingAs($user, 'staff')
        ->delete(route('staff.apps.destroy', $app))
        ->assertRedirect(route('staff.apps.index'));

    expect(App::find($app->id))->toBeNull();
});

test('staff user cannot delete another users app', function () {
    fakeHydra();
    $user = createStaffUser();
    $otherApp = App::factory()->create();

    $this->actingAs($user, 'staff')
        ->delete(route('staff.apps.destroy', $otherApp))
        ->assertForbidden();
});

test('staff user can regenerate secret for their own app', function () {
    fakeHydra();
    $user = createStaffUser();
    $app = App::factory()->create(['user_id' => $user->id, 'client_id' => 'test-client-id']);

    $this->actingAs($user, 'staff')
        ->post(route('staff.apps.regenerate-secret', $app))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Staff/Apps/AppEdit', false)
            ->has('clientSecret')
        );

    $app->refresh();
    expect($app->client_secret)->not->toBeNull();
});

test('staff user cannot regenerate secret for another users app', function () {
    fakeHydra();
    $user = createStaffUser();
    $otherApp = App::factory()->create();

    $this->actingAs($user, 'staff')
        ->post(route('staff.apps.regenerate-secret', $otherApp))
        ->assertForbidden();
});

test('create app validation requires client_name and redirect_uris', function () {
    fakeHydra();
    $user = createStaffUser();

    $this->actingAs($user, 'staff')
        ->post(route('staff.apps.store'), [])
        ->assertSessionHasErrors(['client_name', 'redirect_uris']);
});

test('create app validation requires valid urls in redirect_uris', function () {
    fakeHydra();
    $user = createStaffUser();

    $this->actingAs($user, 'staff')
        ->post(route('staff.apps.store'), [
            'client_name' => 'Test',
            'redirect_uris' => ['not-a-url'],
        ])
        ->assertSessionHasErrors(['redirect_uris.0']);
});
