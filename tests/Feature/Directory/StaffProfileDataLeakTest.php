<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Enums\StaffProfileVisibility;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsStaffProfileConsent;

uses(RefreshDatabase::class, GrantsStaffProfileConsent::class);

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
        'pronouns' => 'she/her',
        'birthdate' => '1990-06-15',
        'phone' => '+49123456789',
        'telegram_username' => 'maria_test',
        'address_line1' => '123 Main St',
        'address_line2' => 'Apt 4B',
        'city' => 'Berlin',
        'postal_code' => '10115',
        'country' => 'DE',
        'emergency_contact_name' => 'Jane Doe',
        'emergency_contact_phone' => '+49987654321',
        'emergency_contact_telegram' => 'jane_doe',
        'spoken_languages' => ['en', 'de'],
        'credit_as' => 'MariaS',
        'staff_profile_visibility' => [
            'firstname' => StaffProfileVisibility::AllStaff->value,
            'lastname' => StaffProfileVisibility::AllStaff->value,
            'pronouns' => StaffProfileVisibility::AllStaff->value,
            'birthdate' => StaffProfileVisibility::DirectorsOnly->value,
            'phone' => StaffProfileVisibility::DirectorsOnly->value,
            'telegram' => StaffProfileVisibility::MyDepartments->value,
            'address' => StaffProfileVisibility::DirectorsOnly->value,
            'emergency_contact' => StaffProfileVisibility::AllStaff->value,
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
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Directory/StaffProfile')
            ->where('profileUser.name', 'TestProfileUser')
            ->where('profileUser.credit_as', 'MariaS')
            ->where('profileUser.spoken_languages', ['en', 'de'])
            ->where('visibleFields.firstname', 'Maria')
            ->where('visibleFields.lastname', 'Schmidt')
            ->where('visibleFields.pronouns', 'she/her')
            ->where('visibleFields.telegram', 'maria_test')
            ->missing('visibleFields.birthdate')
            ->missing('visibleFields.phone')
            ->where('visibleFields.emergency_contact_name', 'Jane Doe')
            ->where('visibleFields.emergency_contact_phone', '+49987654321')
            ->where('visibleFields.emergency_contact_telegram', 'jane_doe')
            ->missing('visibleFields.address_line1')
            ->missing('visibleFields.address_line2')
            ->missing('visibleFields.city')
            ->missing('visibleFields.postal_code')
            ->missing('visibleFields.country')
        );
});

test('different-department staff cannot see MyDepartments fields', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'otherDept' => $od, 'profileUser' => $pu] = createProfileScenario();
    $viewer = makeViewer($sg, $od);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
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
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
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
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('profileUser.email')
            ->missing('profileUser.password')
            ->missing('profileUser.firstname')
            ->missing('profileUser.lastname')
            ->missing('profileUser.pronouns')
            ->missing('profileUser.birthdate')
            ->missing('profileUser.phone')
            ->missing('profileUser.telegram_username')
            ->missing('profileUser.telegram_id')
            ->missing('profileUser.staff_profile_visibility')
            ->missing('profileUser.id')
            ->missing('profileUser.two_factor_secret')
            ->missing('profileUser.two_factor_recovery_codes')
            ->missing('profileUser.remember_token')
            ->missing('profileUser.address_line1')
            ->missing('profileUser.address_line2')
            ->missing('profileUser.city')
            ->missing('profileUser.postal_code')
            ->missing('profileUser.country')
            ->missing('profileUser.emergency_contact_name')
            ->missing('profileUser.emergency_contact_phone')
            ->missing('profileUser.emergency_contact_telegram')
        );
});

test('all fields restricted hides everything from regular staff', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'profileUser' => $pu] = createProfileScenario();

    $pu->update([
        'staff_profile_visibility' => [
            'firstname' => StaffProfileVisibility::DirectorsOnly->value,
            'lastname' => StaffProfileVisibility::DirectorsOnly->value,
            'pronouns' => StaffProfileVisibility::DirectorsOnly->value,
            'birthdate' => StaffProfileVisibility::DirectorsOnly->value,
            'phone' => StaffProfileVisibility::DirectorsOnly->value,
            'telegram' => StaffProfileVisibility::DirectorsOnly->value,
            'address' => StaffProfileVisibility::DirectorsOnly->value,
            'emergency_contact' => StaffProfileVisibility::DirectorsOnly->value,
        ],
    ]);

    $viewer = makeViewer($sg);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('profileUser.name', 'TestProfileUser')
            ->where('profileUser.credit_as', 'MariaS')
            ->where('visibleFields', [])
        );
});

test('non-staff user cannot access staff profile', function () {
    ['department' => $dept, 'profileUser' => $pu] = createProfileScenario();

    $nonStaff = User::factory()->create();
    $nonStaff->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $this->actingAs($nonStaff)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
        ->assertForbidden();
});

test('groups prop only contains safe fields', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'profileUser' => $pu] = createProfileScenario();
    $viewer = makeViewer($sg, $dept);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
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
            'pronouns' => StaffProfileVisibility::LeadsAndDirectors->value,
            'birthdate' => StaffProfileVisibility::LeadsAndDirectors->value,
            'phone' => StaffProfileVisibility::LeadsAndDirectors->value,
            'telegram' => StaffProfileVisibility::LeadsAndDirectors->value,
            'address' => StaffProfileVisibility::LeadsAndDirectors->value,
            'emergency_contact' => StaffProfileVisibility::LeadsAndDirectors->value,
        ],
    ]);

    $teamLead = makeViewer($sg, $dept, GroupUserLevel::TeamLead);
    $regularMember = makeViewer($sg, $dept);

    $this->actingAs($teamLead)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('visibleFields.firstname', 'Maria')
            ->where('visibleFields.phone', '+49123456789')
            ->where('visibleFields.address_line1', '123 Main St')
            ->where('visibleFields.emergency_contact_name', 'Jane Doe')
        );

    $this->actingAs($regularMember)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('visibleFields', [])
        );
});

test('partial address still emits all five keys atomically when visible', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'profileUser' => $pu] = createProfileScenario();
    $pu->update([
        'address_line1' => 'Only Line 1',
        'address_line2' => null,
        'city' => null,
        'postal_code' => null,
        'country' => null,
    ]);

    $director = makeViewer($sg, $dept, GroupUserLevel::Director);

    $this->actingAs($director)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('visibleFields.address_line1', 'Only Line 1')
            ->where('visibleFields.address_line2', null)
            ->where('visibleFields.city', null)
            ->where('visibleFields.postal_code', null)
            ->where('visibleFields.country', null)
        );
});

test('address defaults to DirectorsOnly and emergency_contact defaults to AllStaff when visibility map is empty', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'profileUser' => $pu] = createProfileScenario();
    // Clear the visibility map entirely to exercise the per-key defaults.
    $pu->update(['staff_profile_visibility' => []]);

    $viewer = makeViewer($sg, $dept);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('visibleFields.emergency_contact_name', 'Jane Doe')
            ->missing('visibleFields.address_line1')
            ->missing('visibleFields.city')
            ->missing('visibleFields.country')
        );
});

test('directory view of a withdrawn user shows empty staff profile data', function () {
    ['staffGroup' => $sg, 'department' => $dept, 'profileUser' => $pu] = createProfileScenario();

    // Grant consent first, then withdraw to wipe all gated data
    $this->grantStaffProfileConsent($pu);
    $this->actingAs($pu)->delete(route('settings.staff-profile.consent.withdraw'));

    $viewer = makeViewer($sg, $dept);

    $this->actingAs($viewer)
        ->get(route('directory.members.show', ['slug' => $dept->slug, 'user' => $pu->hashid]))
        ->assertOk()
        ->assertDontSee('Maria')
        ->assertDontSee('Schmidt')
        ->assertDontSee('123 Main St')
        ->assertDontSee('+49123456789')
        ->assertDontSee('Jane Doe')
        ->assertInertia(fn ($page) => $page
            ->where('visibleFields.firstname', null)
            ->where('visibleFields.lastname', null)
            ->where('visibleFields.phone', null)
            ->where('visibleFields.emergency_contact_name', null)
            ->where('visibleFields.emergency_contact_phone', null)
            ->where('visibleFields.emergency_contact_telegram', null)
            ->missing('visibleFields.address_line1')
            ->where('profileUser.credit_as', null)
            ->where('profileUser.spoken_languages', null)
        );
});
