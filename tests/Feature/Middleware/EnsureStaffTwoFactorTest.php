<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createStaffMember(): User
{
    $user = User::factory()->create();
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
        'slug' => 'staff',
    ]);
    $staffGroup->users()->attach($user->id, ['level' => GroupUserLevel::Member]);

    return $user;
}

test('staff without totp is redirected to totp setup from dashboard', function () {
    $user = createStaffMember();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('settings.security.totp'));
});

test('staff without totp can access totp setup page', function () {
    $user = createStaffMember();

    $this->actingAs($user)
        ->get(route('settings.security.totp'))
        ->assertSuccessful();
});

test('staff without totp can access totp setup endpoint', function () {
    $user = createStaffMember();

    $this->actingAs($user)
        ->get(route('settings.two-factor.totp.setup'))
        ->assertSuccessful();
});

test('staff without totp is redirected from security overview', function () {
    $user = createStaffMember();

    $this->actingAs($user)
        ->get(route('settings.security'))
        ->assertRedirect(route('settings.security.totp'));
});

test('staff with totp can access dashboard', function () {
    $user = createStaffMember();
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful();
});

test('non-staff user without totp can access dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful();
});
