<?php

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupVisibilityScenario(): array
{
    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $department = Group::factory()->create(['type' => 'department', 'name' => 'Art']);
    $otherDepartment = Group::factory()->create(['type' => 'department', 'name' => 'Tech']);

    $owner = User::factory()->create();
    $owner->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $owner->groups()->attach($department, ['level' => GroupUserLevel::Member]);

    $sameDept = User::factory()->create();
    $sameDept->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $sameDept->groups()->attach($department, ['level' => GroupUserLevel::Member]);

    $diffDept = User::factory()->create();
    $diffDept->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $diffDept->groups()->attach($otherDepartment, ['level' => GroupUserLevel::Member]);

    $teamLead = User::factory()->create();
    $teamLead->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $teamLead->groups()->attach($otherDepartment, ['level' => GroupUserLevel::TeamLead]);

    $director = User::factory()->create();
    $director->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $director->groups()->attach($otherDepartment, ['level' => GroupUserLevel::Director]);

    $nonStaff = User::factory()->create();

    return compact('owner', 'sameDept', 'diffDept', 'teamLead', 'director', 'nonStaff');
}

it('AllStaff: any staff can see, non-staff cannot', function () {
    $s = setupVisibilityScenario();
    $s['owner']->update(['staff_profile_visibility' => ['firstname' => 'all_staff']]);

    expect($s['owner']->canViewStaffField('firstname', $s['sameDept']))->toBeTrue();
    expect($s['owner']->canViewStaffField('firstname', $s['diffDept']))->toBeTrue();
    expect($s['owner']->canViewStaffField('firstname', $s['teamLead']))->toBeTrue();
    expect($s['owner']->canViewStaffField('firstname', $s['director']))->toBeTrue();
    expect($s['owner']->canViewStaffField('firstname', $s['nonStaff']))->toBeFalse();
});

it('MyDepartments: only co-members can see', function () {
    $s = setupVisibilityScenario();
    $s['owner']->update(['staff_profile_visibility' => ['phone' => 'my_departments']]);

    expect($s['owner']->canViewStaffField('phone', $s['sameDept']))->toBeTrue();
    expect($s['owner']->canViewStaffField('phone', $s['diffDept']))->toBeFalse();
    expect($s['owner']->canViewStaffField('phone', $s['nonStaff']))->toBeFalse();
});

it('LeadsAndDirectors: only lead roles can see', function () {
    $s = setupVisibilityScenario();
    $s['owner']->update(['staff_profile_visibility' => ['birthdate' => 'leads_and_directors']]);

    expect($s['owner']->canViewStaffField('birthdate', $s['sameDept']))->toBeFalse();
    expect($s['owner']->canViewStaffField('birthdate', $s['teamLead']))->toBeTrue();
    expect($s['owner']->canViewStaffField('birthdate', $s['director']))->toBeTrue();
    expect($s['owner']->canViewStaffField('birthdate', $s['nonStaff']))->toBeFalse();
});

it('DirectorsOnly: only directors can see', function () {
    $s = setupVisibilityScenario();
    $s['owner']->update(['staff_profile_visibility' => ['phone' => 'directors_only']]);

    expect($s['owner']->canViewStaffField('phone', $s['sameDept']))->toBeFalse();
    expect($s['owner']->canViewStaffField('phone', $s['teamLead']))->toBeFalse();
    expect($s['owner']->canViewStaffField('phone', $s['director']))->toBeTrue();
    expect($s['owner']->canViewStaffField('phone', $s['nonStaff']))->toBeFalse();
});

it('defaults to AllStaff when visibility is not set', function () {
    $s = setupVisibilityScenario();
    $s['owner']->update(['staff_profile_visibility' => null]);

    expect($s['owner']->canViewStaffField('firstname', $s['sameDept']))->toBeTrue();
    expect($s['owner']->canViewStaffField('firstname', $s['nonStaff']))->toBeFalse();
});

it('defaults to AllStaff when field key is missing from visibility', function () {
    $s = setupVisibilityScenario();
    $s['owner']->update(['staff_profile_visibility' => ['phone' => 'directors_only']]);

    expect($s['owner']->canViewStaffField('firstname', $s['sameDept']))->toBeTrue();
});
