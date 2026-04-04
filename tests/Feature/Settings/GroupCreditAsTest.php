<?php

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createStaffUserWithGroups(): array
{
    $user = User::factory()->create();
    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $department = Group::factory()->create(['type' => 'department', 'name' => 'Art']);
    $team = Group::factory()->create(['type' => 'team', 'name' => 'Design']);

    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $user->groups()->attach($department, ['level' => GroupUserLevel::Member, 'title' => 'Artist']);
    $user->groups()->attach($team, ['level' => GroupUserLevel::TeamLead, 'title' => 'Lead']);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    return [$user, $department, $team];
}

it('allows staff to update credit_as for their groups', function () {
    [$user, $department, $team] = createStaffUserWithGroups();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.credit-as'), [
            'groups' => [
                ['group_id' => $department->id, 'credit_as' => 'ArtistName'],
                ['group_id' => $team->id, 'credit_as' => 'DesignerName'],
            ],
        ])
        ->assertRedirect();

    expect($user->groups()->find($department->id)->pivot->credit_as)->toBe('ArtistName');
    expect($user->groups()->find($team->id)->pivot->credit_as)->toBe('DesignerName');
});

it('clears credit_as when null is sent', function () {
    [$user, $department] = createStaffUserWithGroups();

    $user->groups()->updateExistingPivot($department->id, ['credit_as' => 'OldName']);

    $this->actingAs($user)
        ->post(route('settings.staff-profile.credit-as'), [
            'groups' => [
                ['group_id' => $department->id, 'credit_as' => null],
            ],
        ])
        ->assertRedirect();

    expect($user->groups()->find($department->id)->pivot->credit_as)->toBeNull();
});

it('rejects groups the user does not belong to', function () {
    [$user] = createStaffUserWithGroups();
    $otherGroup = Group::factory()->create(['type' => 'department']);

    $this->actingAs($user)
        ->post(route('settings.staff-profile.credit-as'), [
            'groups' => [
                ['group_id' => $otherGroup->id, 'credit_as' => 'Hacker'],
            ],
        ])
        ->assertSessionHasErrors('groups.0.group_id');
});

it('rejects non-staff users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.credit-as'), [
            'groups' => [],
        ])
        ->assertForbidden();
});

it('rejects unauthenticated users', function () {
    $this->post(route('settings.staff-profile.credit-as'), [
        'groups' => [],
    ])->assertRedirect();
});

it('validates credit_as max length', function () {
    [$user, $department] = createStaffUserWithGroups();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.credit-as'), [
            'groups' => [
                ['group_id' => $department->id, 'credit_as' => str_repeat('a', 101)],
            ],
        ])
        ->assertSessionHasErrors('groups.0.credit_as');
});

it('accepts empty groups array', function () {
    [$user] = createStaffUserWithGroups();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.credit-as'), [
            'credit_as' => 'MyName',
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->credit_as)->toBe('MyName');
});
