<?php

use App\Models\User;
use App\Notifications\PasswordResetQueuedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('reset-passwords:127.0.0.1');
});

test('forgot password page loads', function () {
    $this->get(route('auth.forgot-password.view'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
});

test('reset link is sent for existing user', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('auth.forgot-password.store'), [
        'email' => $user->email,
    ])
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/ForgotPassword')
            ->where('status', __('passwords.sent'))
        );

    Notification::assertSentTo($user, PasswordResetQueuedNotification::class);
});

test('non-existent email shows same success message to prevent user enumeration', function () {
    Notification::fake();

    $this->post(route('auth.forgot-password.store'), [
        'email' => 'nonexistent@example.com',
    ])
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/ForgotPassword')
            ->where('status', __('passwords.sent'))
        );

    Notification::assertNothingSent();
});

test('email field is required', function () {
    $this->post(route('auth.forgot-password.store'), [
        'email' => '',
    ])->assertSessionHasErrors('email');
});

test('email must be valid format', function () {
    $this->post(route('auth.forgot-password.store'), [
        'email' => 'not-an-email',
    ])->assertSessionHasErrors('email');
});

test('route throttle middleware blocks after 5 attempts with 429', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('auth.forgot-password.store'), [
            'email' => $user->email,
        ])->assertSuccessful();
    }

    $this->post(route('auth.forgot-password.store'), [
        'email' => $user->email,
    ])->assertTooManyRequests();
});

test('rate limiter error message is translatable', function () {
    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('reset-passwords:127.0.0.1');
    }

    $this->post(route('auth.forgot-password.store'), [
        'email' => 'any@example.com',
    ])
        ->assertSessionHasErrors([
            'email' => __('passwords.throttled'),
        ]);
});

test('activity is logged for existing user', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('auth.forgot-password.store'), [
        'email' => $user->email,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'description' => 'mail-reset-password',
    ]);
});

test('no activity logged for non-existent user', function () {
    Notification::fake();

    $this->post(route('auth.forgot-password.store'), [
        'email' => 'nobody@example.com',
    ]);

    $this->assertDatabaseMissing('activity_log', [
        'description' => 'mail-reset-password',
    ]);
});
