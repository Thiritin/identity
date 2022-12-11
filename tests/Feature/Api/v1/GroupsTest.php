<?php

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\Role;
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

uses(RefreshDatabase::class);

test('Create Group success as Admin', function () {
    $group = Group::factory()->create();
    $role = Role::findOrCreate('superadmin');

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $user->assignRole('superadmin');

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Owner]]);
    $data = [
        "type" => "none",
        "name" => "Testgroup",
        "logo" => "https://test.de/img-create-group-test.png"
    ];
    $request = postJson(route('api.v1.groups.store'), $data);
    $request->assertSuccessful();
    assertDatabaseHas('groups', [
        "type" => "none",
        "logo" => "https://test.de/img-create-group-test.png"
    ]);
});

test('Create Group fails as non Admin', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Owner]]);
    $data = [
        "type" => "none",
        "name" => "Testgroup",
        "logo" => "https://test.de/img-create-group-test.png"
    ];
    $request = postJson(route('api.v1.groups.store'), $data);
    $request->assertForbidden();
    assertDatabaseMissing('groups', [
        "type" => "none",
        "logo" => "https://test.de/img-create-group-test.png"
    ]);
});

test('Update Group success as Admin', function () {
    $group = Group::factory()->create();
    $role = Role::findOrCreate('superadmin');

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $user->assignRole('superadmin');

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Owner]]);
    $data = [
        "type" => "none",
        "name" => "Testgroup",
        "logo" => "https://test.de/img-create-group-test.png"
    ];
    $request = put(route('api.v1.groups.update', $group), $data);
    $request->assertSuccessful();
    assertDatabaseHas('groups', [
        "type" => "none",
        "logo" => "https://test.de/img-create-group-test.png"
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
        "type" => "none",
        "name" => "Testgroup",
        "logo" => "https://test.de/img-create-group-test.png"
    ];
    $request = put(route('api.v1.groups.update', $group), $data);
    $request->assertForbidden();
    assertDatabaseMissing('groups', [
        "type" => "none",
        "logo" => "https://test.de/img-create-group-test.png"
    ]);
});


test('Update Group fails as invited', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Invited]]);
    $data = [
        "type" => "none",
        "name" => "Testgroup",
        "logo" => "https://test.de/img-create-group-test.png"
    ];
    $request = put(route('api.v1.groups.update', $group), $data);
    $request->assertForbidden();
    assertDatabaseMissing('groups', [
        "type" => "none",
        "logo" => "https://test.de/img-create-group-test.png"
    ]);
});


test('Update Group fails as banned', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Banned]]);
    $data = [
        "type" => "none",
        "name" => "Testgroup",
        "logo" => "https://test.de/img-create-group-test.png"
    ];
    $request = put(route('api.v1.groups.update', $group), $data);
    $request->assertForbidden();
    assertDatabaseMissing('groups', [
        "type" => "none",
        "logo" => "https://test.de/img-create-group-test.png"
    ]);
});

test('Update Group fails as moderator', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Moderator]]);
    $data = [
        "type" => "none",
        "name" => "Testgroup",
        "logo" => "https://test.de/img-create-group-test.png"
    ];
    $request = put(route('api.v1.groups.update', $group), $data);
    $request->assertForbidden();
    assertDatabaseMissing('groups', [
        "type" => "none",
        "logo" => "https://test.de/img-create-group-test.png"
    ]);
});

test('Update Group success as Owner', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Owner]]);
    $data = [
        "type" => "none",
        "name" => "Testgroup",
        "logo" => "https://test.de/img-update-as-owner-group-test.png"
    ];
    $request = put(route('api.v1.groups.update', $group), $data);
    $request->assertSuccessful();
    assertDatabaseHas('groups', [
        "type" => "none",
        "logo" => "https://test.de/img-update-as-owner-group-test.png"
    ]);
});

test('Delete Group success as Owner', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );

    assertDatabaseHas('groups', [
        "logo" => $group->logo
    ]);
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Owner]]);
    $request = delete(route('api.v1.groups.destroy', $group));
    $request->assertSuccessful();
    assertDatabaseMissing('groups', [
        "logo" => $group->logo
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
        "data" => [
            "name" => $group->name
        ]
    ]);
});

test('Get paginated result set of group as Admin', function () {
    $group = Group::factory()->create();

    $role = Role::findOrCreate('superadmin');
    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $user->assignRole('superadmin');
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Member]]);
    $request = get(route('api.v1.groups.index', $group));
    $request->assertSuccessful();
    $request->assertJsonFragment([
        "name" => $group->name
    ]);
});

test('Invite member to group as admin', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $userToBeInvited = User::factory()->create();
    $data = [
        "email" => $userToBeInvited->email,
        "level" => "invited"
    ];
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Admin]]);

    $request = post(route('api.v1.groups.users.store', $group), $data);
    $request->assertSuccessful();
    assertDatabaseHas('group_user', [
        "user_id" => $userToBeInvited->id,
        "group_id" => $group->id,
        "level" => "invited"
    ]);
});

test('Add member to group as admin', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read', 'groups.update', 'groups.delete']
    );
    $userToBeInvited = User::factory()->create();
    $data = [
        "email" => $userToBeInvited->email,
        "level" => "member"
    ];
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Admin]]);

    $request = post(route('api.v1.groups.users.store', $group), $data);
    $request->assertSuccessful();
    assertDatabaseHas('group_user', [
        "user_id" => $userToBeInvited->id,
        "group_id" => $group->id,
        "level" => "member"
    ]);
});

test('Add member to group as admin without correct scope', function () {
    $group = Group::factory()->create();

    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups.read']
    );
    $userToBeInvited = User::factory()->create();
    $data = [
        "email" => $userToBeInvited->email,
        "level" => "member"
    ];
    $group->users()->sync([$user->id => ['level' => GroupUserLevel::Admin]]);

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
        "email" => $userToBeInvited->email,
        "level" => "member"
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
        $user->id => ['level' => GroupUserLevel::Admin],
        $userToBeDeleted->id => ['level' => GroupUserLevel::Member]
    ]);

    assertDatabaseHas('group_user', [
        "user_id" => $userToBeDeleted->id,
        "group_id" => $group->id,
    ]);
    $request = delete(route('api.v1.groups.users.destroy', ["group" => $group, "user" => $userToBeDeleted]));
    $request->assertSuccessful();
    assertDatabaseMissing('group_user', [
        "user_id" => $userToBeDeleted->id,
        "group_id" => $group->id,
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
        $user->id => ['level' => GroupUserLevel::Admin],
        $userToBeDeleted->id => ['level' => GroupUserLevel::Member]
    ]);

    $request = get(route('api.v1.groups.users.index', ["group" => $group]));
    $request->assertSuccessful();
    $request->assertJsonFragment([
        "user_id" => $user->id,
        "group_id" => $group->id,
        "level" => GroupUserLevel::Admin
    ]);
});

