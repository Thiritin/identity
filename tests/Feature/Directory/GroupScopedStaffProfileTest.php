<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupGroupScopedScenario(): array
{
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);

    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $division = Group::factory()->division()->create(['name' => 'Operations', 'parent_id' => $root->id]);
    $department = Group::factory()->department()->create(['name' => 'Art Department', 'parent_id' => $division->id]);

    $target = User::factory()->create(['name' => 'TargetUser']);
    $target->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($target, ['level' => GroupUserLevel::Member]);
    $department->users()->attach($target, ['level' => GroupUserLevel::Member, 'title' => 'Artist']);

    return compact('staffGroup', 'division', 'department', 'target');
}

function createViewer(Group $staffGroup, ?Group $group = null, GroupUserLevel $level = GroupUserLevel::Member): User
{
    $viewer = User::factory()->create();
    $viewer->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($viewer, ['level' => GroupUserLevel::Member]);
    if ($group) {
        $group->users()->attach($viewer, ['level' => $level]);
    }

    return $viewer;
}

it('shows edit controls for a director viewing a member', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'target' => $target] = setupGroupScopedScenario();
    $director = createViewer($sg, $dept, GroupUserLevel::Director);

    $this->actingAs($director)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $target->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Directory/StaffProfile')
            ->where('group.hashid', $dept->hashid)
            ->where('groupMembership.title', 'Artist')
            ->where('canEdit', true)
        );
});

it('hides edit controls for a regular member', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'target' => $target] = setupGroupScopedScenario();
    $member = createViewer($sg, $dept, GroupUserLevel::Member);

    $this->actingAs($member)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $target->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canEdit', false)
        );
});

it('includes breadcrumbs with group ancestry', function () {
    ['staffGroup' => $sg, 'division' => $div, 'department' => $dept, 'target' => $target] = setupGroupScopedScenario();
    $viewer = createViewer($sg, $dept);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $target->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('breadcrumbs', 4)
            ->where('breadcrumbs.0.label', __('directory'))
            ->where('breadcrumbs.1.label', 'Operations')
            ->where('breadcrumbs.2.label', 'Art Department')
            ->where('breadcrumbs.3.label', 'TargetUser')
            ->where('breadcrumbs.3.href', null)
        );
});

it('returns 404 for old non-scoped member route', function () {
    ['staffGroup' => $sg, 'target' => $target] = setupGroupScopedScenario();
    $viewer = createViewer($sg);

    $this->actingAs($viewer)
        ->get('/directory/members/' . $target->hashid)
        ->assertNotFound();
});

it('shows null group membership when user is not in the viewed group', function () {
    ['staffGroup' => $sg, 'department' => $dept] = setupGroupScopedScenario();

    // Create a user who is staff but NOT in the department
    $outsider = User::factory()->create(['name' => 'OutsiderUser']);
    $outsider->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $sg->users()->attach($outsider, ['level' => GroupUserLevel::Member]);

    $viewer = createViewer($sg, $dept);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $outsider->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('groupMembership', null)
        );
});
