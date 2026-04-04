<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupManager(): array
{
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $division = Group::factory()->division()->create(['name' => 'Test Division', 'parent_id' => $root->id]);
    $department = Group::factory()->department()->create(['name' => 'Test Dept', 'parent_id' => $division->id]);

    $manager = User::factory()->create();
    $manager->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($manager, ['level' => GroupUserLevel::Member]);
    $department->users()->attach($manager, ['level' => GroupUserLevel::Director]);

    return [$manager, $department, $staffGroup, $root];
}

test('manager can update group description', function () {
    [$manager, $department] = setupManager();

    $this->actingAs($manager)
        ->post(route('directory.update', $department), ['description' => 'Updated description'])
        ->assertRedirect();

    expect($department->fresh()->description)->toBe('Updated description');
});

test('regular member cannot update group', function () {
    [$manager, $department, $staffGroup] = setupManager();

    $regularUser = User::factory()->create();
    $regularUser->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($regularUser, ['level' => GroupUserLevel::Member]);
    $department->users()->attach($regularUser, ['level' => GroupUserLevel::Member]);

    $this->actingAs($regularUser)
        ->post(route('directory.update', $department), ['description' => 'Nope'])
        ->assertForbidden();
});

test('manager can add member to group', function () {
    [$manager, $department] = setupManager();
    $newUser = User::factory()->create();

    $this->actingAs($manager)
        ->post(route('directory.members.store', $department), ['user_hashid' => $newUser->hashid])
        ->assertRedirect();

    expect($department->users()->where('user_id', $newUser->id)->exists())->toBeTrue();
});

test('manager can update member level', function () {
    [$manager, $department] = setupManager();
    $member = User::factory()->create();
    $department->users()->attach($member, ['level' => GroupUserLevel::Member]);

    $this->actingAs($manager)
        ->patch(route('directory.members.update', [$department, $member]), [
            'level' => GroupUserLevel::TeamLead->value,
            'title' => 'Lead',
            'can_manage_members' => true,
        ])
        ->assertRedirect();

    $pivot = $department->users()->find($member)->pivot;
    expect($pivot->level)->toBe(GroupUserLevel::TeamLead);
    expect($pivot->title)->toBe('Lead');
    expect($pivot->can_manage_members)->toBeTrue();
});

test('manager can remove member', function () {
    [$manager, $department] = setupManager();
    $member = User::factory()->create();
    $department->users()->attach($member, ['level' => GroupUserLevel::Member]);

    $this->actingAs($manager)
        ->delete(route('directory.members.destroy', [$department, $member]))
        ->assertRedirect();

    expect($department->users()->where('user_id', $member->id)->exists())->toBeFalse();
});

test('manager can create team under department', function () {
    [$manager, $department] = setupManager();

    $this->actingAs($manager)
        ->post(route('directory.teams.store', $department), ['name' => 'New Team'])
        ->assertRedirect();

    expect(Group::where('name', 'New Team')->where('type', GroupTypeEnum::Team)->exists())->toBeTrue();
});

test('manager can delete team', function () {
    [$manager, $department] = setupManager();
    $team = Group::factory()->team()->create(['name' => 'Temp Team', 'parent_id' => $department->id]);

    $this->actingAs($manager)
        ->delete(route('directory.destroy', $team))
        ->assertRedirect();

    expect(Group::find($team->id))->toBeNull();
});

test('cannot delete non-team group', function () {
    [$manager, $department] = setupManager();

    $this->actingAs($manager)
        ->delete(route('directory.destroy', $department))
        ->assertForbidden();
});

test('manager can update group icon', function () {
    [$manager, $department] = setupManager();

    $this->actingAs($manager)
        ->post(route('directory.update', $department), ['icon' => 'shield'])
        ->assertRedirect();

    expect($department->fresh()->icon)->toBe('shield');
});

test('invalid icon is rejected', function () {
    [$manager, $department] = setupManager();

    $this->actingAs($manager)
        ->post(route('directory.update', $department), ['icon' => 'not-a-real-icon'])
        ->assertSessionHasErrors('icon');
});

test('null icon clears existing icon', function () {
    [$manager, $department] = setupManager();
    $department->update(['icon' => 'shield']);

    $this->actingAs($manager)
        ->post(route('directory.update', $department), ['icon' => null])
        ->assertRedirect();

    expect($department->fresh()->icon)->toBeNull();
});
