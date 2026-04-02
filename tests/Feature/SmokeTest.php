<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\App;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Traits\InteractsWithHydra;

uses(RefreshDatabase::class, InteractsWithHydra::class);

/*
|--------------------------------------------------------------------------
| Guest Pages (no auth required)
|--------------------------------------------------------------------------
*/

test('register page loads', function () {
    $this->withSession(['auth.email_flow.email' => 'test@example.com'])
        ->get(route('auth.register.view'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/Register'));
});

test('forgot password page loads', function () {
    $this->get(route('auth.forgot-password.view'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
});

test('error page loads', function () {
    $this->get(route('auth.error', ['error' => 'test', 'error_description' => 'test error']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/Error')
            ->where('title', 'test')
            ->where('description', 'test error'));
});

test('health endpoint returns ok', function () {
    $this->get(route('health'))
        ->assertSuccessful();
});

/*
|--------------------------------------------------------------------------
| Auth Pages with Hydra (OIDC login flow)
|--------------------------------------------------------------------------
*/

test('login page loads with valid login challenge', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test-challenge-123',
            'client' => ['client_id' => 'test-client', 'client_name' => 'Test'],
            'request_url' => 'http://localhost',
            'requested_scope' => ['openid'],
            'skip' => false,
            'subject' => '',
        ]),
    ]);

    // First request validates challenge and redirects
    $this->get(route('auth.login.view', ['login_challenge' => 'test-challenge-123']))
        ->assertRedirect(route('auth.login.view'));

    // Second request renders the email page
    $this->get(route('auth.login.view'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/Email'));
});

test('login page without challenge redirects to portal login', function () {
    $this->get(route('auth.login.view'))
        ->assertRedirect(route('login.apps.redirect', ['app' => 'portal']));
});

test('login submit with valid credentials returns redirect', function () {
    $loginRequestResponse = [
        'challenge' => 'test-challenge-123',
        'client' => ['client_id' => 'smoke-test-client'],
        'request_url' => 'http://localhost',
        'requested_scope' => ['openid', 'email', 'profile'],
        'skip' => false,
        'subject' => '',
    ];

    Http::fake(function ($request) use ($loginRequestResponse) {
        if (str_contains($request->url(), '/admin/clients')) {
            return Http::response([
                'client_id' => 'smoke-test-client',
                'client_name' => 'smoke-test',
                'client_secret' => 'test-secret',
                'redirect_uris' => ['http://localhost:9999/callback'],
                'grant_types' => ['authorization_code'],
                'response_types' => ['code'],
                'scope' => 'openid email profile',
                'token_endpoint_auth_method' => 'client_secret_post',
            ]);
        }

        if (str_contains($request->url(), '/requests/login/accept')) {
            return Http::response(['redirect_to' => 'http://localhost/consent']);
        }

        if (str_contains($request->url(), '/requests/login')) {
            return Http::response($loginRequestResponse);
        }

        if (str_contains($request->url(), '/oauth2/auth')) {
            return Http::response('', 302, [
                'Location' => 'http://localhost/login?login_challenge=test-challenge-123',
            ]);
        }

        return Http::response('', 200);
    });

    $this->createHydraClient();
    $challenge = $this->getLoginChallenge();

    $password = 'TestPassword123!';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    App::withoutEvents(function () use ($user) {
        App::create([
            'client_id' => 'smoke-test-client',
            'name' => 'smoke-test',
            'system_name' => 'portal',
            'user_id' => $user->id,
        ]);
    });

    $response = $this->withSession([
        'auth.login_challenge' => ['challenge' => $challenge, 'client_id' => 'smoke-test-client'],
        'auth.email_flow.email' => $user->email,
    ])->post(route('auth.login.password.submit'), [
        'email' => $user->email,
        'password' => $password,
        'remember' => false,
    ]);

    $response->assertRedirect();

    $this->deleteHydraClient();
});

test('login submit with wrong password returns validation error', function () {
    Http::fake([
        '*/admin/clients*' => Http::response([
            'client_id' => 'smoke-test-client',
            'client_name' => 'smoke-test',
            'client_secret' => 'test-secret',
            'redirect_uris' => ['http://localhost:9999/callback'],
            'grant_types' => ['authorization_code'],
            'response_types' => ['code'],
            'scope' => 'openid email profile',
            'token_endpoint_auth_method' => 'client_secret_post',
        ]),
        '*/oauth2/auth*' => Http::response('', 302, [
            'Location' => 'http://localhost/login?login_challenge=test-challenge-123',
        ]),
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'challenge' => 'test-challenge-123',
            'client' => ['client_id' => 'smoke-test-client'],
            'request_url' => 'http://localhost',
            'requested_scope' => ['openid', 'email', 'profile'],
            'skip' => false,
            'subject' => '',
        ]),
    ]);

    $this->createHydraClient();
    $challenge = $this->getLoginChallenge();

    $user = User::factory()->create([
        'password' => Hash::make('CorrectPassword123!'),
    ]);

    $this->withSession([
        'auth.login_challenge' => ['challenge' => $challenge, 'client_id' => 'smoke-test-client'],
    ])->postJson(route('auth.login.password.submit'), [
        'email' => $user->email,
        'password' => 'WrongPassword123!',
        'remember' => false,
    ])->assertJsonValidationErrorFor('nouser');

    $this->deleteHydraClient();
});

/*
|--------------------------------------------------------------------------
| Authenticated Portal Pages
|--------------------------------------------------------------------------
*/

test('dashboard loads for regular user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('dashboard loads for staff users', function () {
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
    ]);
    $user = User::factory()->create();
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('root redirects to login when unauthenticated', function () {
    $this->get('/')
        ->assertRedirect();
});

test('root redirects to dashboard when authenticated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

/*
|--------------------------------------------------------------------------
| Settings Pages (authenticated)
|--------------------------------------------------------------------------
*/

test('profile settings page loads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.profile'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Settings/Profile'));
});

test('update password redirects to security', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/update-password')
        ->assertRedirect('/settings/security');
});

test('two factor redirects to security', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/two-factor')
        ->assertRedirect('/settings/security');
});

test('totp setup redirects to security', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/two-factor/totp')
        ->assertRedirect('/settings/security');
});

test('yubikey setup redirects to security', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/two-factor/yubikey')
        ->assertRedirect('/settings/security');
});

/*
|--------------------------------------------------------------------------
| Settings Pages redirect unauthenticated users
|--------------------------------------------------------------------------
*/

test('settings pages require authentication', function (string $url) {
    $this->get($url)->assertRedirect();
})->with([
    'profile' => '/settings/profile',
    'security' => '/settings/security',
]);

/*
|--------------------------------------------------------------------------
| Staff Pages (staff guard)
|--------------------------------------------------------------------------
*/

test('staff dashboard loads for staff user', function () {
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
    ]);
    $user = User::factory()->create();
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $this->actingAs($user, 'staff')
        ->get(route('staff.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Staff/Dashboard'));
});

test('staff groups index loads', function () {
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
    ]);
    $user = User::factory()->create();
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $this->actingAs($user, 'staff')
        ->get(route('staff.groups.index'))
        ->assertSuccessful();
});

test('staff group show page loads', function () {
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
    ]);
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $this->actingAs($user, 'staff')
        ->get(route('staff.groups.show', $group))
        ->assertSuccessful();
});

/*
|--------------------------------------------------------------------------
| API Endpoints
|--------------------------------------------------------------------------
*/

test('api userinfo returns user data with sanctum token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->getJson(route('api.v1.userinfo'), [
        'Authorization' => 'Bearer ' . $token,
    ])->assertSuccessful();
});

test('api userinfo requires authentication', function () {
    $this->getJson(route('api.v1.userinfo'))
        ->assertUnauthorized();
});

test('api groups index requires authentication', function () {
    $this->getJson(route('api.v1.groups.index'))
        ->assertUnauthorized();
});

/*
|--------------------------------------------------------------------------
| Email Verification
|--------------------------------------------------------------------------
*/

test('verify email page loads for unverified user', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertSuccessful();
});
