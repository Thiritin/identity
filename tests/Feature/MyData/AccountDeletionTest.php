<?php

use App\Models\OauthSession;
use App\Models\User;
use App\Services\Hydra\Client as HydraClient;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('deletes account when no active registration', function () {
    $this->mock(RegistrationService::class, function ($mock) {
        $mock->shouldReceive('hasActiveRegistration')->andReturn(false);
    });

    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('revokeAllConsentSessions')->once();
        $mock->shouldReceive('invalidateAllSessions')->once();
    });

    $user = User::factory()->create();
    OauthSession::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('my-data.delete-account'))
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('oauth_sessions', ['user_id' => $user->id]);
});

it('blocks deletion when active registration exists', function () {
    $this->mock(RegistrationService::class, function ($mock) {
        $mock->shouldReceive('hasActiveRegistration')->andReturn(true);
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('my-data.delete-account'))
        ->assertRedirect()
        ->assertSessionHasErrors('delete');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

it('blocks deletion when registration service fails', function () {
    $this->mock(RegistrationService::class, function ($mock) {
        $mock->shouldReceive('hasActiveRegistration')
            ->andThrow(new RuntimeException('Service unavailable'));
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('my-data.delete-account'))
        ->assertRedirect()
        ->assertSessionHasErrors('delete');

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

it('requires authentication', function () {
    $this->delete(route('my-data.delete-account'))
        ->assertRedirect();
});
