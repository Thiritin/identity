<?php

use App\Domains\Staff\Enums\GroupTypeEnum;
use App\Domains\Staff\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;
use function Pest\Laravel\put;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

test('Create Group success as Admin', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    
    // Make user a director in a group to grant admin permissions
    $adminGroup = Group::factory()->create(['type' => GroupTypeEnum::BOD]);
    $adminGroup->users()->attach($user->id, [
        'level' => GroupUserLevel::Director,
        'can_manage_users' => true
    ]);

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Director]]);
    $data = [
        'type' => 'none',
        'name' => 'Testgroup',
    ];
    $request = postJson(route('api.v1.groups.store'), $data);
    $request->assertSuccessful();
    assertDatabaseHas('groups', [
        'type' => 'none',
    ]);
});

test('Create Group and validate user is set as owner', function () {
    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    
    // Make user a director in a BOD group to grant admin permissions
    $adminGroup = Group::factory()->create(['type' => GroupTypeEnum::BOD]);
    $adminGroup->users()->attach($user->id, [
        'level' => GroupUserLevel::Director,
        'can_manage_users' => true
    ]);

    $data = [
        'type' => 'none',
        'name' => 'Testgroup',
    ];
    $request = postJson(route('api.v1.groups.store'), $data);
    $request->assertSuccessful();
    assertDatabaseHas('group_user', [
        'level' => 'director',
        'group_id' => Hashids::connection('group')->decode($request->json('data')['id'])[0],
        'user_id' => $user->id,
    ]);
});

test('Create Group fails as non Admin', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Director]]);
    $data = [
        'type' => 'none',
        'name' => 'Testgroup',
    ];
    $request = postJson(route('api.v1.groups.store'), $data);
    $request->assertForbidden();
    assertDatabaseMissing('groups', [
        'type' => 'none',
        'name' => 'Testgroup',
    ]);
});

test('Update Group success as Admin', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Director]]);
    $data = [
        'type' => 'none',
        'name' => 'Testgroup',
    ];
    // Assert $user is level owner in group
    assertDatabaseHas('group_user', [
        'level' => 'director',
        'group_id' => $group->id,
        'user_id' => $user->id,

    ]);
    $request = putJson(route('api.v1.groups.update', $group), $data);
    $request->assertSuccessful();
    assertDatabaseHas('groups', [
        'type' => 'none',
    ]);
});

test('Update Group fails as member', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Member]]);
    $data = [
        'type' => 'none',
        'name' => 'Testgroup',
    ];
    $request = put(route('api.v1.groups.update', $group), $data);
    $request->assertForbidden();
    assertDatabaseMissing('groups', [
        'type' => 'none',
        'name' => 'Testgroup',
    ]);
});

test('Update Group fails as moderator', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::TeamLead]]);
    $data = [
        'type' => 'none',
        'name' => 'Testgroup',
    ];
    $request = put(route('api.v1.groups.update', $group), $data);
    $request->assertForbidden();
    assertDatabaseMissing('groups', [
        'type' => 'none',
        'name' => 'Testgroup',
    ]);
});

test('Update Group success as Owner', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Director]]);
    $data = [
        'type' => 'none',
        'name' => 'Testgroup',
    ];
    $request = put(route('api.v1.groups.update', $group), $data);
    $request->assertSuccessful();
    assertDatabaseHas('groups', [
        'type' => 'none',
        'name' => 'Testgroup',
    ]);
});

test('Delete Group success as Owner', function () {
    $group = Group::factory()->create([
        'type' => GroupTypeEnum::Team,
    ]);

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    assertDatabaseHas('groups', [
        'logo' => $group->logo,
    ]);
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Director]]);
    $request = delete(route('api.v1.groups.destroy', $group));
    $request->assertSuccessful();
    assertDatabaseMissing('groups', [
        'logo' => $group->logo,
    ]);
});

test('Delete Group success as non-owner', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Member]]);
    $request = delete(route('api.v1.groups.destroy', $group));
    $request->assertForbidden();
});

test('Get single group', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Member]]);
    $request = get(route('api.v1.groups.show', $group));
    $request->assertSuccessful();
    $request->assertJson([
        'data' => [
            'name' => $group->name,
        ],
    ]);
});

test('Get paginated result set of group as Admin', function () {
    $group = Group::factory()->create();
    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Member]]);
    $request = get(route('api.v1.groups.index', $group));
    $request->assertSuccessful();
    $request->assertJsonFragment([
        'name' => $group->name,
    ]);
});

test('Add member to group as admin via email', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $userToBeInvited = User::factory()->create();
    $data = [
        'email' => $userToBeInvited->email,
        'level' => 'member',
    ];
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::TeamLead]]);

    $request = post(route('api.v1.groups.users.store', $group), $data);
    $request->assertSuccessful();
    // verify response contains user_id, group_id and level
    $request->assertJsonFragment([
        'user_id' => $userToBeInvited->hashid,
        'group_id' => $group->hashid,
        'level' => 'member',
    ]);
    assertDatabaseHas('group_user', [
        'user_id' => $userToBeInvited->id,
        'group_id' => $group->id,
        'level' => 'member',
    ]);
});

// Add member twice should cause error via email
test('Adding the same email twice should cause an error', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $userToBeInvited = User::factory()->create();
    $data = [
        'email' => $userToBeInvited->email,
        'level' => 'member',
    ];
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::TeamLead]]);

    $request = post(route('api.v1.groups.users.store', $group), $data);
    $request->assertSuccessful();
    // verify response contains user_id, group_id and level
    $request->assertJsonFragment([
        'user_id' => $userToBeInvited->hashid,
        'group_id' => $group->hashid,
        'level' => 'member',
    ]);
    assertDatabaseHas('group_user', [
        'user_id' => $userToBeInvited->id,
        'group_id' => $group->id,
        'level' => 'member',
    ]);

    $request = postJson(route('api.v1.groups.users.store', $group), $data, ['Accept' => 'application/json']);
    $request->assertJsonValidationErrors(['email']);
});

test('Add member to group as admin via id', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $userToBeInvited = User::factory()->create();
    $data = [
        'id' => $userToBeInvited->hashid,
        'level' => 'member',
    ];
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::TeamLead]]);

    $request = post(route('api.v1.groups.users.store', $group), $data);
    $request->assertSessionHasNoErrors();
    $request->assertSuccessful();
    // verify response contains user_id, group_id and level
    $request->assertJsonFragment([
        'user_id' => $userToBeInvited->hashid,
        'group_id' => $group->hashid,
        'level' => 'member',
    ]);
    assertDatabaseHas('group_user', [
        'user_id' => $userToBeInvited->id,
        'group_id' => $group->id,
        'level' => 'member',
    ]);
});

test('Fail add member when specifying both id and email', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $userToBeInvited = User::factory()->create();
    $data = [
        'id' => $userToBeInvited->hashid,
        'email' => $userToBeInvited->email,
        'level' => 'member',
    ];
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::TeamLead]]);

    $request = postJson(route('api.v1.groups.users.store', $group), $data, ['Accept' => 'application/json']);
    $request->assertJsonValidationErrorFor('id');
    $request->assertJsonValidationErrorFor('email');
});

test('Add member to group as admin without correct scope', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read']
    );
    $userToBeInvited = User::factory()->create();
    $data = [
        'email' => $userToBeInvited->email,
        'level' => 'member',
    ];
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::TeamLead]]);

    $request = post(route('api.v1.groups.users.store', $group), $data);
    $request->assertForbidden();
});

test('Add member to group as member with correct scope', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $userToBeInvited = User::factory()->create();
    $data = [
        'email' => $userToBeInvited->email,
        'level' => 'member',
    ];
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Member]]);

    $request = post(route('api.v1.groups.users.store', $group), $data);
    $request->assertForbidden();
});

test('Remove member to group as admin', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $userToBeDeleted = User::factory()->create();
    $group->users()->sync([
        $user->id => ['level' => GroupUserLevel::TeamLead],
        $userToBeDeleted->id => ['level' => GroupUserLevel::Member],
    ]);

    assertDatabaseHas('group_user', [
        'user_id' => $userToBeDeleted->id,
        'group_id' => $group->id,
    ]);
    $request = delete(route('api.v1.groups.users.destroy', ['group' => $group, 'user' => $userToBeDeleted]));
    $request->assertSuccessful();
    assertDatabaseMissing('group_user', [
        'user_id' => $userToBeDeleted->id,
        'group_id' => $group->id,
    ]);
});

test('Get list of members as admin', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $userToBeDeleted = User::factory()->create();
    $group->users()->sync([
        $user->id => ['level' => GroupUserLevel::TeamLead],
        $userToBeDeleted->id => ['level' => GroupUserLevel::Member],
    ]);

    $request = get(route('api.v1.groups.users.index', ['group' => $group]));
    $request->assertSuccessful();
    $request->assertJsonFragment([
        'user_id' => $user->hashid,
        'group_id' => $group->hashid,
        'level' => 'team_lead',
    ]);
});

test('Ensure normal user can only see their own groups via groups index', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read']
    );

    $request = get(route('api.v1.groups.index', $group));
    $request->assertSuccessful();
    $request->assertJsonMissing([
        'name' => $group->name,
    ]);
});

test('Ensure that staff user can see all department and own groups', function () {
    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read']
    );
    // Add user to group with system_name staff, create it first
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
    ]);
    // Add user to group
    $staffGroup->users()->sync([$user->id => ['level' => GroupUserLevel::Member]]);

    // Other Group of type none
    $groupMem = Group::factory()->create([
        'type' => GroupTypeEnum::Default,
    ]);
    // Add user to group
    $groupMem->users()->sync([$user->id => ['level' => GroupUserLevel::Member]]);

    // Other group where user is not member
    $group2 = Group::factory()->create([
        'type' => GroupTypeEnum::Default,
    ]);
    // Remove user
    $group2->users()->detach($user->id);

    $group = Group::factory()->create([
        'type' => GroupTypeEnum::Department,
    ]);
    // Add user to group
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Member]]);

    $request = get(route('api.v1.groups.index'));
    $request->assertSuccessful();
    $request->assertJsonFragment([
        'name' => $groupMem->name,
    ]);
    $request->assertJsonFragment([
        'name' => $staffGroup->name,
    ]);
    // group2 should be missing
    $request->assertJsonMissing([
        'name' => $group2->name,
    ]);
});
