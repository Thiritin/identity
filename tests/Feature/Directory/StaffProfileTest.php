<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Enums\StaffProfileVisibility;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createStaffGroup(): Group
{
    return Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
}

function makeStaffUser(Group $staffGroup, ?Group $group = null, GroupUserLevel $level = GroupUserLevel::Member): User
{
    $user = User::factory()->create();
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);
    if ($group) {
        $group->users()->attach($user, ['level' => $level]);
    }

    return $user;
}

test('canViewStaffField returns true for AllStaff visibility when viewer is staff', function () {
    $staffGroup = createStaffGroup();
    $profileUser = makeStaffUser($staffGroup);
    $profileUser->update([
        'firstname' => 'John',
        'staff_profile_visibility' => ['firstname' => StaffProfileVisibility::AllStaff->value],
    ]);
    $viewer = makeStaffUser($staffGroup);
    expect($profileUser->canViewStaffField('firstname', $viewer))->toBeTrue();
});

test('canViewStaffField returns false for AllStaff when viewer is not staff', function () {
    $staffGroup = createStaffGroup();
    $profileUser = makeStaffUser($staffGroup);
    $profileUser->update([
        'firstname' => 'John',
        'staff_profile_visibility' => ['firstname' => StaffProfileVisibility::AllStaff->value],
    ]);
    $nonStaffViewer = User::factory()->create();
    expect($profileUser->canViewStaffField('firstname', $nonStaffViewer))->toBeFalse();
});

test('canViewStaffField defaults to AllStaff when visibility is null', function () {
    $staffGroup = createStaffGroup();
    $profileUser = makeStaffUser($staffGroup);
    $profileUser->update(['firstname' => 'John', 'staff_profile_visibility' => null]);
    $viewer = makeStaffUser($staffGroup);
    expect($profileUser->canViewStaffField('firstname', $viewer))->toBeTrue();
});

test('canViewStaffField MyDepartments requires shared group', function () {
    $staffGroup = createStaffGroup();
    $department = Group::factory()->department()->create(['name' => 'Dept']);
    $profileUser = makeStaffUser($staffGroup, $department);
    $profileUser->update([
        'firstname' => 'John',
        'staff_profile_visibility' => ['firstname' => StaffProfileVisibility::MyDepartments->value],
    ]);
    $viewerInSameGroup = makeStaffUser($staffGroup, $department);
    $viewerInDifferentGroup = makeStaffUser($staffGroup);
    expect($profileUser->canViewStaffField('firstname', $viewerInSameGroup))->toBeTrue();
    expect($profileUser->canViewStaffField('firstname', $viewerInDifferentGroup))->toBeFalse();
});

test('canViewStaffField LeadsAndDirectors requires lead role', function () {
    $staffGroup = createStaffGroup();
    $department = Group::factory()->department()->create(['name' => 'Dept']);
    $profileUser = makeStaffUser($staffGroup, $department);
    $profileUser->update([
        'phone' => '123',
        'staff_profile_visibility' => ['phone' => StaffProfileVisibility::LeadsAndDirectors->value],
    ]);
    $director = makeStaffUser($staffGroup, $department, GroupUserLevel::Director);
    $regularMember = makeStaffUser($staffGroup, $department, GroupUserLevel::Member);
    expect($profileUser->canViewStaffField('phone', $director))->toBeTrue();
    expect($profileUser->canViewStaffField('phone', $regularMember))->toBeFalse();
});

test('canViewStaffField DirectorsOnly requires director level', function () {
    $staffGroup = createStaffGroup();
    $department = Group::factory()->department()->create(['name' => 'Dept']);
    $profileUser = makeStaffUser($staffGroup, $department);
    $profileUser->update([
        'birthdate' => '1990-01-01',
        'staff_profile_visibility' => ['birthdate' => StaffProfileVisibility::DirectorsOnly->value],
    ]);
    $divisionDirector = makeStaffUser($staffGroup, $department, GroupUserLevel::DivisionDirector);
    $teamLead = makeStaffUser($staffGroup, $department, GroupUserLevel::TeamLead);
    expect($profileUser->canViewStaffField('birthdate', $divisionDirector))->toBeTrue();
    expect($profileUser->canViewStaffField('birthdate', $teamLead))->toBeFalse();
});
