<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\App;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\InteractsWithHydra;

uses(RefreshDatabase::class, InteractsWithHydra::class);

/*
|--------------------------------------------------------------------------
| Guest Pages (no auth required)
|--------------------------------------------------------------------------
*/

test('choose page loads', function () {
    $this->get(route('auth.choose'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/Choose'));
});

test('register page loads', function () {
    $this->get(route('auth.register.view'))
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
    $this->createHydraClient();
    $challenge = $this->getLoginChallenge();

    $this->get(route('auth.login.view', ['login_challenge' => $challenge]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/Login'));

    $this->deleteHydraClient();
});

test('login page without challenge redirects to choose', function () {
    $this->get(route('auth.login.view'))
        ->assertRedirect(route('auth.choose'));
});

test('login submit with valid credentials returns redirect', function () {
    $hydraClient = $this->createHydraClient();
    $challenge = $this->getLoginChallenge();

    $password = 'TestPassword123!';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);

    // Create an App record matching the Hydra client so checkEmailVerification works
    // Use withoutEvents to skip AppObserver which would try to create a second Hydra client
    App::withoutEvents(function () use ($hydraClient, $user) {
        App::create([
            'client_id' => $hydraClient['client_id'],
            'name' => 'smoke-test',
            'system_name' => 'portal',
            'user_id' => $user->id,
        ]);
    });

    $response = $this->post(route('auth.login.submit'), [
        'login_challenge' => $challenge,
        'email' => $user->email,
        'password' => $password,
        'remember' => false,
    ]);

    // Successful login redirects to Hydra's consent flow
    $response->assertRedirect();

    $this->deleteHydraClient();
});

test('login submit with wrong password returns validation error', function () {
    $this->createHydraClient();
    $challenge = $this->getLoginChallenge();

    $user = User::factory()->create([
        'password' => Hash::make('CorrectPassword123!'),
    ]);

    $this->postJson(route('auth.login.submit'), [
        'login_challenge' => $challenge,
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

test('update password page loads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.update-password'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Settings/UpdatePassword'));
});

test('two factor settings page loads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.two-factor'))
        ->assertSuccessful();
});

test('totp setup page loads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.two-factor.totp'))
        ->assertSuccessful();
});

test('yubikey setup page loads', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.two-factor.yubikey'))
        ->assertSuccessful();
});

/*
|--------------------------------------------------------------------------
| Settings Pages redirect unauthenticated users
|--------------------------------------------------------------------------
*/

test('settings pages require authentication', function (string $routeName) {
    $this->get(route($routeName))
        ->assertRedirect();
})->with([
    'profile' => 'settings.profile',
    'update-password' => 'settings.update-password',
    'two-factor' => 'settings.two-factor',
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
