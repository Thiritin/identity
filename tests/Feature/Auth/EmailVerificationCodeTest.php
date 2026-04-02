<?php

use App\Models\User;
use App\Notifications\VerifyEmailCodeNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('unverified user sees code page on verification notice', function () {
    Notification::fake();

    $user = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/VerifyCode')
            ->has('submitRoute')
            ->has('resendRoute')
            ->where('showLogout', true)
        );

    Notification::assertSentTo($user, VerifyEmailCodeNotification::class);
});

test('verified user is redirected away from verification notice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertRedirect(route('dashboard'));
});

test('unverified user can verify with correct code', function () {
    Event::fake([Verified::class]);

    $user = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->withSession([
            'auth.verify_email_code' => [
                'code' => 'ABC123',
                'expires_at' => now()->addMinutes(15),
            ],
        ])
        ->post(route('verification.submit'), ['code' => 'abc123'])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->email_verified_at)->not->toBeNull();
    Event::assertDispatched(Verified::class);
});

test('wrong code returns error', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->withSession([
            'auth.verify_email_code' => [
                'code' => 'ABC123',
                'expires_at' => now()->addMinutes(15),
            ],
        ])
        ->post(route('verification.submit'), ['code' => 'WRONG1'])
        ->assertSessionHasErrors('code');
});

test('expired code returns error', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->withSession([
            'auth.verify_email_code' => [
                'code' => 'ABC123',
                'expires_at' => now()->subMinutes(1),
            ],
        ])
        ->post(route('verification.submit'), ['code' => 'ABC123'])
        ->assertSessionHasErrors('code');
});

test('resend generates new code and sends notification', function () {
    Notification::fake();

    $user = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->post(route('verification.resend'))
        ->assertRedirect();

    Notification::assertSentTo($user, VerifyEmailCodeNotification::class);
});

test('submit without session code redirects to verification notice', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    $this->actingAs($user)
        ->post(route('verification.submit'), ['code' => 'ABC123'])
        ->assertRedirect(route('verification.notice'));
});

test('already verified user submitting code is redirected to dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.submit'), ['code' => 'ABC123'])
        ->assertRedirect(route('dashboard'));
});

test('already verified user resending is redirected to dashboard', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.resend'))
        ->assertRedirect(route('dashboard'));

    Notification::assertNothingSent();
});

test('guest cannot access verification routes', function () {
    $this->get(route('verification.notice'))->assertRedirect();
    $this->post(route('verification.submit'), ['code' => 'ABC123'])->assertRedirect();
    $this->post(route('verification.resend'))->assertRedirect();
});

test('code is not regenerated if still valid', function () {
    Notification::fake();

    $user = User::factory()->create(['email_verified_at' => null]);

    // First visit generates a code
    $this->actingAs($user)
        ->get(route('verification.notice'));

    Notification::assertSentToTimes($user, VerifyEmailCodeNotification::class, 1);

    // Second visit should not regenerate
    $this->actingAs($user)
        ->get(route('verification.notice'));

    Notification::assertSentToTimes($user, VerifyEmailCodeNotification::class, 1);
});
