<?php

use App\Models\User;
use App\Services\Hydra\Client as HydraClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exports user data as JSON', function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('getConsentSessions')->andReturn([]);
    });

    $user = User::factory()->create(['name' => 'ExportTest']);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->get(route('my-data.export'))
        ->assertOk()
        ->assertDownload();
});

it('requires authentication', function () {
    $this->get(route('my-data.export'))
        ->assertRedirect();
});
