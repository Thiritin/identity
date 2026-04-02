<?php

use App\Models\User;
use App\Notifications\VerifyEmailCodeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\post;
use function PHPUnit\Framework\assertEquals;

uses(RefreshDatabase::class);

test('register form stores data in session and redirects to verify', function () {
    Http::fake();
    $response = post(route('auth.register.store'), [
        'username' => 'Test',
        'email' => 'test@eurofurence.org',
        'password' => 'OSANR&dbb^0GDp^19UiSxRlM3Wm',
    ]);
    $response->assertRedirect(route('auth.register.verify'));
    expect(session('auth.register.username'))->toBe('Test');
    expect(session('auth.register.email'))->toBe('test@eurofurence.org');
});

test('altcha verify creates account and sends code', function () {
    Http::fake();
    Notification::fake();
    Event::fake();

    $this->withSession([
        'auth.register' => [
            'username' => 'Test',
            'email' => 'test@eurofurence.org',
            'password' => 'OSANR&dbb^0GDp^19UiSxRlM3Wm',
        ],
    ])->post(route('auth.register.verify.submit'), [
        'altcha' => config('altcha.testing_bypass'),
    ])->assertRedirect(route('auth.register.code'));

    Event::assertDispatched(Registered::class);
    $user = User::where('email', 'test@eurofurence.org')->first();
    expect($user)->not->toBeNull();
    expect($user->email_verified_at)->toBeNull();
    Notification::assertSentTo($user, VerifyEmailCodeNotification::class);
});

test('verify code activates account', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->withSession([
        'auth.verify_code' => [
            'user_id' => $user->id,
            'code' => '123456',
            'expires_at' => now()->addMinutes(15),
        ],
    ])->post(route('auth.register.code.submit'), [
        'code' => '123456',
    ])->assertRedirect(route('login.apps.redirect', ['app' => 'portal']));

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

test('wrong code returns error', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->withSession([
        'auth.verify_code' => [
            'user_id' => $user->id,
            'code' => '123456',
            'expires_at' => now()->addMinutes(15),
        ],
    ])->post(route('auth.register.code.submit'), [
        'code' => '999999',
    ])->assertSessionHasErrors('code');
});

test('expired code returns error', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->withSession([
        'auth.verify_code' => [
            'user_id' => $user->id,
            'code' => '123456',
            'expires_at' => now()->subMinutes(1),
        ],
    ])->post(route('auth.register.code.submit'), [
        'code' => '123456',
    ])->assertSessionHasErrors('code');
});

test('Check logs dispatch - Register', function () {
    Mail::fake();
    $user = User::factory()->create();
    event(new Registered($user));
    assertEquals('registered', $user->actions->first()->description);
});
