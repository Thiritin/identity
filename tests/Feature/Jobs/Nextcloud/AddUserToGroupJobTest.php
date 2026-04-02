<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Jobs\Nextcloud\AddUserToGroupJob;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.nextcloud.baseUrl', 'https://cloud.example.com');
    config()->set('services.nextcloud.username', 'admin');
    config()->set('services.nextcloud.password', 'secret');
});

it('adds user to group', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder', 'nextcloud_folder_id' => 1]);
    $user = User::factory()->create();

    (new AddUserToGroupJob($group, $user, GroupUserLevel::Member))->handle();

    Http::assertSent(function ($request) use ($user) {
        return str_contains($request->url(), "ocs/v2.php/cloud/users/{$user->hashid}/groups")
            && $request->method() === 'POST';
    });
});

it('sets ACL for team lead on non-team group', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test Folder',
        'nextcloud_folder_id' => 42,
        'type' => GroupTypeEnum::Department,
    ]);
    $user = User::factory()->create();

    (new AddUserToGroupJob($group, $user, GroupUserLevel::TeamLead))->handle();

    Http::assertSent(function ($request) use ($group, $user) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL")
            && $request['mappingId'] === $user->hashid
            && $request['manageAcl'] === '1';
    });
});

it('sets ACL for manager-flagged member on non-team group', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test Folder',
        'nextcloud_folder_id' => 42,
        'type' => GroupTypeEnum::Department,
    ]);
    $user = User::factory()->create();

    (new AddUserToGroupJob($group, $user, GroupUserLevel::Member, true))->handle();

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL")
            && $request['manageAcl'] === '1';
    });
});

it('does not set ACL for member', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test Folder',
        'nextcloud_folder_id' => 42,
        'type' => GroupTypeEnum::Department,
    ]);
    $user = User::factory()->create();

    (new AddUserToGroupJob($group, $user, GroupUserLevel::Member))->handle();

    Http::assertNotSent(function ($request) use ($group) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL");
    });
});

it('does not set ACL for team groups', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Team Folder',
        'nextcloud_folder_id' => 99,
        'type' => GroupTypeEnum::Team,
    ]);
    $user = User::factory()->create();

    (new AddUserToGroupJob($group, $user, GroupUserLevel::TeamLead))->handle();

    Http::assertNotSent(function ($request) use ($group) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL");
    });
});

it('creates user if not found in Nextcloud then adds to group', function () {
    $userCreated = false;

    Http::fake(function ($request) use (&$userCreated) {
        // GET check user exists - return 404 first time
        if ($request->method() === 'GET' && str_contains($request->url(), 'ocs/v2.php/cloud/users/')) {
            return Http::response('<ocs><meta><statuscode>404</statuscode></meta><data></data></ocs>', 404);
        }

        // POST create user
        if ($request->method() === 'POST' && str_contains($request->url(), 'ocs/v2.php/cloud/users')
            && ! str_contains($request->url(), '/groups')) {
            $userCreated = true;

            return Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200);
        }

        return Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200);
    });

    $group = Group::factory()->create(['nextcloud_folder_name' => 'Test Folder', 'nextcloud_folder_id' => 1]);
    $user = User::factory()->create();

    (new AddUserToGroupJob($group, $user, GroupUserLevel::Member))->handle();

    expect($userCreated)->toBeTrue();
});
