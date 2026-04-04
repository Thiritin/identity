<?php

use App\Models\User;
use App\Services\Hydra\Client as HydraClient;
use App\Services\Hydra\HydraRequestException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('revokes consent for an app', function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('revokeConsentSession')
            ->once()
            ->with(Mockery::type('string'), 'test-app');
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('my-data.revoke-app', 'test-app'))
        ->assertRedirect();
});

it('returns error when hydra revocation fails', function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('revokeConsentSession')
            ->andThrow(new HydraRequestException('Failed', 500));
    });

    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('my-data.revoke-app', 'test-app'))
        ->assertRedirect()
        ->assertSessionHasErrors();
});

it('requires authentication to revoke consent', function () {
    $this->delete(route('my-data.revoke-app', 'test-app'))
        ->assertRedirect();
});
