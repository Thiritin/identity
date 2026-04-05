<?php

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsStaffProfileConsent;

uses(RefreshDatabase::class, GrantsStaffProfileConsent::class);

function makeStaffUserForConsentGate(): User
{
    $user = User::factory()->create();
    $staffGroup = Group::firstWhere('system_name', 'staff')
        ?? Group::factory()->create(['system_name' => 'staff']);
    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    return $user->fresh();
}

test('un-consented staff user is blocked from updating staff profile', function () {
    $user = makeStaffUserForConsentGate();
    $response = $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), ['firstname' => 'Alice']);
    $response->assertSessionHasErrors('_consent');
    expect($user->fresh()->firstname)->toBeNull();
});

test('un-consented staff user is blocked from updating group credit_as', function () {
    $user = makeStaffUserForConsentGate();
    $response = $this->actingAs($user)
        ->post(route('settings.staff-profile.credit-as'), ['credit_as' => 'Alice']);
    $response->assertSessionHasErrors('_consent');
});

test('un-consented staff user is blocked from updating convention attendance', function () {
    $user = makeStaffUserForConsentGate();
    $response = $this->actingAs($user)
        ->post(route('settings.staff-profile.conventions'), []);
    $response->assertSessionHasErrors('_consent');
});

test('consented staff user can update staff profile normally', function () {
    $user = $this->grantStaffProfileConsent(makeStaffUserForConsentGate());
    $response = $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), ['firstname' => 'Alice']);
    $response->assertSessionHasNoErrors();
    expect($user->fresh()->firstname)->toBe('Alice');
});

test('non-staff users hit 403 on the middleware', function () {
    $user = User::factory()->create(); // non-staff
    $response = $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), ['firstname' => 'Alice']);
    $response->assertForbidden();
});
