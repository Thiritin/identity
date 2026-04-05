<?php

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use App\Services\Hydra\Client as HydraClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsStaffProfileConsent;

uses(RefreshDatabase::class, GrantsStaffProfileConsent::class);

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

it('includes staff profile consent state in export for consented staff user', function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('getConsentSessions')->andReturn([]);
    });

    $user = User::factory()->create(['name' => 'ConsentExport']);
    $staffGroup = Group::firstWhere('system_name', 'staff')
        ?? Group::factory()->create(['system_name' => 'staff']);
    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $this->grantStaffProfileConsent($user);

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->get(route('my-data.export'));

    $response->assertOk();

    $json = json_decode($response->streamedContent(), true);
    expect($json['profile'])->toHaveKey('staff_profile_consent_at');
    expect($json['profile'])->toHaveKey('staff_profile_consent_version');
    expect($json['profile']['staff_profile_consent_at'])->not->toBeNull();
    expect($json['profile']['staff_profile_consent_version'])->toBe(1);
});

it('requires authentication', function () {
    $this->get(route('my-data.export'))
        ->assertRedirect();
});
