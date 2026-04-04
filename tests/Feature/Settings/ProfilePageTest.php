<?php

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the profile page for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.profile'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Profile')
        );
});

it('passes staff profile data when user is staff', function () {
    $user = User::factory()->create([
        'firstname' => 'John',
        'lastname' => 'Doe',
    ]);

    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $department = Group::factory()->create(['type' => 'department', 'name' => 'Art']);

    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $user->groups()->attach($department, [
        'level' => GroupUserLevel::Member,
        'title' => 'Artist',
        'credit_as' => 'JD',
    ]);

    $this->actingAs($user)
        ->get(route('settings.profile'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Profile')
            ->has('staffProfile')
            ->has('groupMemberships')
            ->has('conventionAttendance')
            ->has('allConventions')
        );
});

it('does not pass staff data for non-staff users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.profile'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Profile')
            ->where('staffProfile', null)
            ->where('groupMemberships', null)
        );
});

it('redirects unauthenticated users', function () {
    $this->get(route('settings.profile'))
        ->assertRedirect();
});
