<?php

use App\Models\App;
use App\Models\OauthSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    App::flushEventListeners();
});

it('loads the sessions page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->get(route('settings.security.sessions'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Security/Sessions')
            ->has('sessions')
            ->has('currentSessionId')
        );
});

it('shows sessions with app names', function () {
    $user = User::factory()->create();
    $app = App::factory()->create(['client_id' => 'wiki-client']);

    OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'session-uuid-1',
        'ip_address' => '192.168.1.1',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
        'last_client_id' => 'wiki-client',
        'authenticated_at' => now()->subHours(2),
        'last_seen_at' => now()->subMinutes(5),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->get(route('settings.security.sessions'))
        ->assertInertia(fn ($page) => $page
            ->has('sessions', 1)
            ->where('sessions.0.session_id', 'session-uuid-1')
            ->where('sessions.0.ip_address', '192.168.1.1')
            ->where('sessions.0.app_name', $app->name)
        );
});

it('falls back to client_id when app not found', function () {
    $user = User::factory()->create();

    OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'session-uuid-2',
        'last_client_id' => 'unknown-client',
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->get(route('settings.security.sessions'))
        ->assertInertia(fn ($page) => $page
            ->where('sessions.0.app_name', 'unknown-client')
        );
});

it('shows session count on security overview', function () {
    $user = User::factory()->create();

    OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'session-1',
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);
    OauthSession::create([
        'user_id' => $user->id,
        'session_id' => 'session-2',
        'authenticated_at' => now(),
        'last_seen_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('settings.security'))
        ->assertInertia(fn ($page) => $page
            ->where('sessionCount', 2)
        );
});

it('requires authentication for sessions page', function () {
    $this->get(route('settings.security.sessions'))->assertRedirect();
});
