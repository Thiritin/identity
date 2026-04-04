<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Enums\StaffProfileVisibility;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createProfileScenario(): array
{
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);

    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $division = Group::factory()->division()->create(['name' => 'Operations', 'parent_id' => $root->id]);
    $department = Group::factory()->department()->create(['name' => 'Art Department', 'parent_id' => $division->id]);
    $otherDept = Group::factory()->department()->create(['name' => 'Tech Department', 'parent_id' => $division->id]);

    $profileUser = User::factory()->create([
        'name' => 'TestProfileUser',
        'email' => 'secret-email@example.com',
        'firstname' => 'Maria',
        'lastname' => 'Schmidt',
        'birthdate' => '1990-06-15',
        'phone' => '+49123456789',
        'telegram_username' => 'maria_test',
        'spoken_languages' => ['en', 'de'],
        'credit_as' => 'MariaS',
        'staff_profile_visibility' => [
            'firstname' => StaffProfileVisibility::AllStaff->value,
            'lastname' => StaffProfileVisibility::AllStaff->value,
            'birthdate' => StaffProfileVisibility::DirectorsOnly->value,
            'phone' => StaffProfileVisibility::DirectorsOnly->value,
            'telegram' => StaffProfileVisibility::MyDepartments->value,
        ],
    ]);
    $profileUser->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($profileUser, ['level' => GroupUserLevel::Member]);
    $department->users()->attach($profileUser, ['level' => GroupUserLevel::Member, 'title' => 'Artist']);

    return compact('staffGroup', 'department', 'otherDept', 'division', 'profileUser');
}

function makeViewer(Group $staffGroup, ?Group $group = null, GroupUserLevel $level = GroupUserLevel::Member): User
{
    $viewer = User::factory()->create();
    $viewer->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($viewer, ['level' => GroupUserLevel::Member]);
    if ($group) {
        $group->users()->attach($viewer, ['level' => $level]);
    }

    return $viewer;
}

test('same-department staff sees AllStaff and MyDepartments fields', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'profileUser' => $pu] = createProfileScenario();
    $viewer = makeViewer($sg, $dept);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', $pu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Directory/StaffProfile')
            ->where('profileUser.name', 'TestProfileUser')
            ->where('profileUser.credit_as', 'MariaS')
            ->where('profileUser.spoken_languages', ['en', 'de'])
            ->where('visibleFields.firstname', 'Maria')
            ->where('visibleFields.lastname', 'Schmidt')
            ->where('visibleFields.telegram', 'maria_test')
            ->missing('visibleFields.birthdate')
            ->missing('visibleFields.phone')
        );
});

test('different-department staff cannot see MyDepartments fields', function () {
    ['staffGroup' => $sg, 'otherDept' => $od, 'profileUser' => $pu] = createProfileScenario();
    $viewer = makeViewer($sg, $od);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', $pu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('visibleFields.firstname', 'Maria')
            ->where('visibleFields.lastname', 'Schmidt')
            ->missing('visibleFields.telegram')
            ->missing('visibleFields.birthdate')
            ->missing('visibleFields.phone')
        );
});

test('director sees all fields including DirectorsOnly', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'profileUser' => $pu] = createProfileScenario();
    $director = makeViewer($sg, $dept, GroupUserLevel::Director);

    $this->actingAs($director)
        ->get(route('directory.members.show', $pu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('visibleFields.firstname', 'Maria')
            ->where('visibleFields.lastname', 'Schmidt')
            ->where('visibleFields.telegram', 'maria_test')
            ->where('visibleFields.birthdate', fn ($value) => str_starts_with($value, '1990-06-15'))
            ->where('visibleFields.phone', '+49123456789')
        );
});

test('profileUser prop never contains sensitive fields', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'profileUser' => $pu] = createProfileScenario();
    $viewer = makeViewer($sg, $dept);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', $pu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('profileUser.email')
            ->missing('profileUser.password')
            ->missing('profileUser.firstname')
            ->missing('profileUser.lastname')
            ->missing('profileUser.birthdate')
            ->missing('profileUser.phone')
            ->missing('profileUser.telegram_username')
            ->missing('profileUser.telegram_id')
            ->missing('profileUser.staff_profile_visibility')
            ->missing('profileUser.id')
            ->missing('profileUser.two_factor_secret')
            ->missing('profileUser.two_factor_recovery_codes')
            ->missing('profileUser.remember_token')
        );
});

test('all fields restricted hides everything from regular staff', function () {
    ['staffGroup' => $sg, 'profileUser' => $pu] = createProfileScenario();

    $pu->update([
        'staff_profile_visibility' => [
            'firstname' => StaffProfileVisibility::DirectorsOnly->value,
            'lastname' => StaffProfileVisibility::DirectorsOnly->value,
            'birthdate' => StaffProfileVisibility::DirectorsOnly->value,
            'phone' => StaffProfileVisibility::DirectorsOnly->value,
            'telegram' => StaffProfileVisibility::DirectorsOnly->value,
        ],
    ]);

    $viewer = makeViewer($sg);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', $pu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('profileUser.name', 'TestProfileUser')
            ->where('profileUser.credit_as', 'MariaS')
            ->where('visibleFields', [])
        );
});

test('non-staff user cannot access staff profile', function () {
    ['profileUser' => $pu] = createProfileScenario();

    $nonStaff = User::factory()->create();
    $nonStaff->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $this->actingAs($nonStaff)
        ->get(route('directory.members.show', $pu))
        ->assertForbidden();
});

test('groups prop only contains safe fields', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'profileUser' => $pu] = createProfileScenario();
    $viewer = makeViewer($sg, $dept);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', $pu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('groups', 1)
            ->where('groups.0.name', 'Art Department')
            ->where('groups.0.title', 'Artist')
            ->has('groups.0.hashid')
            ->has('groups.0.type')
            ->has('groups.0.level')
            ->missing('groups.0.id')
            ->missing('groups.0.parent_id')
            ->missing('groups.0.pivot')
        );
});

test('LeadsAndDirectors visibility allows team leads but not regular members', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'profileUser' => $pu] = createProfileScenario();

    $pu->update([
        'staff_profile_visibility' => [
            'firstname' => StaffProfileVisibility::LeadsAndDirectors->value,
            'lastname' => StaffProfileVisibility::LeadsAndDirectors->value,
            'birthdate' => StaffProfileVisibility::LeadsAndDirectors->value,
            'phone' => StaffProfileVisibility::LeadsAndDirectors->value,
            'telegram' => StaffProfileVisibility::LeadsAndDirectors->value,
        ],
    ]);

    $teamLead = makeViewer($sg, $dept, GroupUserLevel::TeamLead);
    $regularMember = makeViewer($sg, $dept);

    $this->actingAs($teamLead)
        ->get(route('directory.members.show', $pu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('visibleFields.firstname', 'Maria')
            ->where('visibleFields.phone', '+49123456789')
        );

    $this->actingAs($regularMember)
        ->get(route('directory.members.show', $pu))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('visibleFields', [])
        );
});
