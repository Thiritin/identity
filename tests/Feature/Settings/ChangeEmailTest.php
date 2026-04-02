<?php

use App\Models\User;
use App\Notifications\UpdateEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()]);
});

it('loads the email page', function () {
    $this->get(route('settings.security.email'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security/Email')
            ->has('currentEmail')
        );
});

it('can request email change', function () {
    Notification::fake();

    $this->post(route('settings.security.email.store'), [
        'email' => 'new@example.com',
    ])->assertSessionHasNoErrors();

    Notification::assertSentOnDemand(UpdateEmailNotification::class);
});

it('validates email is required', function () {
    $this->post(route('settings.security.email.store'), [
        'email' => '',
    ])->assertSessionHasErrors('email');
});

it('validates email format', function () {
    $this->post(route('settings.security.email.store'), [
        'email' => 'not-an-email',
    ])->assertSessionHasErrors('email');
});

it('validates email is unique', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('settings.security.email.store'), [
        'email' => 'taken@example.com',
    ])->assertSessionHasErrors('email');
});

it('allows submitting own current email', function () {
    Notification::fake();

    $this->post(route('settings.security.email.store'), [
        'email' => $this->user->email,
    ])->assertSessionHasNoErrors();
});

it('requires sudo mode for POST', function () {
    // Make a fresh request without sudo session
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.security.email.store'), [
            'email' => 'new@example.com',
        ])->assertRedirect();
});

it('requires authentication', function () {
    auth()->logout();

    $this->get(route('settings.security.email'))
        ->assertRedirect();
});
