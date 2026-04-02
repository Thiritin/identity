<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('isSuspended returns false for active users', function () {
    $user = User::factory()->create();

    expect($user->isSuspended())->toBeFalse();
});

it('isSuspended returns true for suspended users', function () {
    $user = User::factory()->suspended()->create();

    expect($user->isSuspended())->toBeTrue();
});

it('suspend sets suspended_at and clears remember_token', function () {
    Http::fake(['*/admin/oauth2/auth/sessions/login*' => Http::response(null, 204)]);

    $user = User::factory()->create(['remember_token' => 'some-token']);

    $user->suspend();
    $user->refresh();

    expect($user->suspended_at)->not->toBeNull()
        ->and($user->remember_token)->toBeNull();
});

it('suspend deletes sanctum tokens', function () {
    Http::fake(['*/admin/oauth2/auth/sessions/login*' => Http::response(null, 204)]);

    $user = User::factory()->create();
    $user->createToken('test-token');

    expect($user->tokens)->toHaveCount(1);

    $user->suspend();

    expect($user->tokens()->count())->toBe(0);
});

it('suspend logs activity', function () {
    Http::fake(['*/admin/oauth2/auth/sessions/login*' => Http::response(null, 204)]);

    $user = User::factory()->create();
    $user->suspend();

    $activity = Activity::where('subject_id', $user->id)
        ->where('subject_type', User::class)
        ->where('description', 'user-suspended')
        ->first();

    expect($activity)->not->toBeNull();
});

it('unsuspend clears suspended_at', function () {
    $user = User::factory()->suspended()->create();

    $user->unsuspend();
    $user->refresh();

    expect($user->suspended_at)->toBeNull();
});

it('unsuspend logs activity', function () {
    $user = User::factory()->suspended()->create();
    $user->unsuspend();

    $activity = Activity::where('subject_id', $user->id)
        ->where('subject_type', User::class)
        ->where('description', 'user-unsuspended')
        ->first();

    expect($activity)->not->toBeNull();
});

it('canAccessPanel blocks suspended admins', function () {
    $user = User::factory()->admin()->suspended()->create();

    expect($user->canAccessPanel(null))->toBeFalse();
});

it('canAccessPanel allows active admins', function () {
    $user = User::factory()->admin()->create();

    expect($user->canAccessPanel(null))->toBeTrue();
});

it('blocks suspended user on skip-login path', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'skip' => true,
            'subject' => 'skip-hashid',
            'client' => ['client_id' => 'test'],
        ]),
    ]);

    $user = User::factory()->suspended()->create(['hashid' => 'skip-hashid']);

    session(['auth.login_challenge.challenge' => 'test-challenge']);
    session(['auth.email_flow.email' => $user->email]);

    $response = $this->get(route('auth.login.password.view'));

    $response->assertRedirect(route('auth.error', [
        'error' => 'account_suspended',
        'error_description' => trans('account_suspended'),
    ]));
});

it('blocks suspended user on attemptLogin path', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/login*' => Http::response([
            'skip' => false,
            'subject' => '',
            'client' => ['client_id' => 'test'],
        ]),
    ]);

    $user = User::factory()->suspended()->create([
        'password' => Hash::make('password123'),
    ]);

    session(['auth.login_challenge.challenge' => 'test-challenge']);
    session(['auth.email_flow.email' => $user->email]);

    $response = $this->post(route('auth.login.password.submit'), [
        'email' => $user->email,
        'password' => 'password123',
        'remember' => false,
    ]);

    $response->assertRedirect(route('auth.error', [
        'error' => 'account_suspended',
        'error_description' => trans('account_suspended'),
    ]));
});

it('blocks suspended user on TwoFactor submit', function () {
    $user = User::factory()->suspended()->create();
    $user->twoFactors()->create([
        'type' => 'totp',
        'secret' => 'test-secret',
    ]);

    $url = URL::signedRoute('auth.two-factor.submit', [
        'login_challenge' => 'test-challenge',
        'user' => $user->hashid,
        'remember' => false,
    ], now()->addMinutes(30));

    $response = $this->post($url, [
        'login_challenge' => 'test-challenge',
        'user' => $user->hashid,
        'code' => '123456',
        'method' => 'totp',
        'remember' => false,
    ]);

    $response->assertRedirect(route('auth.error', [
        'error' => 'account_suspended',
        'error_description' => trans('account_suspended'),
    ]));
});

it('blocks suspended user in ConsentController', function () {
    Http::fake([
        '*/admin/oauth2/auth/requests/consent*' => Http::sequence()
            ->push([
                'subject' => 'consent-hashid',
                'challenge' => 'consent-challenge',
                'requested_scope' => ['openid'],
                'requested_access_token_audience' => [],
                'client' => ['client_id' => 'test'],
            ])
            ->push(['redirect_to' => 'https://example.com/callback']),
    ]);

    $user = User::factory()->suspended()->create(['hashid' => 'consent-hashid']);

    $response = $this->get(route('auth.consent', ['consent_challenge' => 'consent-challenge']));

    $response->assertRedirect();
});

it('returns null from ApiGuard for suspended user', function () {
    $user = User::factory()->suspended()->create(['hashid' => 'api-guard-hashid']);

    Http::fake([
        '*/admin/oauth2/introspect*' => Http::response([
            'active' => true,
            'sub' => 'api-guard-hashid',
            'aud' => [],
            'client_id' => 'test',
            'exp' => time() + 3600,
            'iat' => time(),
            'nbf' => time(),
            'iss' => 'test',
            'token_type' => 'Bearer',
            'token_use' => 'access_token',
            'scope' => 'openid',
        ]),
    ]);

    $guard = app('auth')->guard('api');
    $request = Request::create('/api/v1/userinfo', 'GET');
    $request->headers->set('Authorization', 'Bearer test-token');
    $guard->setRequest($request);

    expect($guard->user())->toBeNull();
});
