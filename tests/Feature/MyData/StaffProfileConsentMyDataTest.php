<?php

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use App\Services\Hydra\Client as HydraClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsStaffProfileConsent;

uses(RefreshDatabase::class, GrantsStaffProfileConsent::class);

function makeStaffUserForMyData(): User
{
    $user = User::factory()->create();
    $staffGroup = Group::firstWhere('system_name', 'staff')
        ?? Group::factory()->create(['system_name' => 'staff']);
    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    return $user->fresh();
}

beforeEach(function () {
    $this->mock(HydraClient::class, function ($mock) {
        $mock->shouldReceive('getConsentSessions')->andReturn([]);
    });
});

test('MyData page shows not-granted state for un-consented staff user', function () {
    $user = makeStaffUserForMyData();

    $response = $this->actingAs($user)->get(route('my-data'));
    $props = $response->viewData('page')['props'];

    expect($props['staffProfileConsent']['granted'])->toBeFalse();
    expect($props['staffProfileConsent']['is_staff'])->toBeTrue();
    expect($props['staffProfileConsent']['granted_at'])->toBeNull();
    expect($props['staffProfileConsent']['version'])->toBeNull();
});

test('MyData page shows granted state', function () {
    $user = $this->grantStaffProfileConsent(makeStaffUserForMyData());

    $response = $this->actingAs($user)->get(route('my-data'));
    $props = $response->viewData('page')['props'];

    expect($props['staffProfileConsent']['granted'])->toBeTrue();
    expect($props['staffProfileConsent']['version'])->toBe(1);
    expect($props['staffProfileConsent']['is_current'])->toBeTrue();
    expect($props['staffProfileConsent']['granted_at'])->not->toBeNull();
});

test('MyData page shows is_staff=false for non-staff users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('my-data'));
    $props = $response->viewData('page')['props'];

    expect($props['staffProfileConsent']['is_staff'])->toBeFalse();
    expect($props['staffProfileConsent']['granted'])->toBeFalse();
});
