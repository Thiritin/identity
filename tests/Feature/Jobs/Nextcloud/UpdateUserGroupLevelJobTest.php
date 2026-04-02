<?php

use App\Enums\GroupUserLevel;
use App\Jobs\Nextcloud\UpdateUserGroupLevelJob;
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

it('enables ACL management when promoted to admin', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $user = User::factory()->create();

    (new UpdateUserGroupLevelJob($group, $user, GroupUserLevel::Admin, GroupUserLevel::Member))->handle();

    Http::assertSent(function ($request) use ($group, $user) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL")
            && $request['mappingId'] === $user->hashid
            && $request['mappingType'] === 'user'
            && $request['manageAcl'] === '1';
    });
});

it('enables ACL management when promoted to owner', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $user = User::factory()->create();

    (new UpdateUserGroupLevelJob($group, $user, GroupUserLevel::Owner, GroupUserLevel::Member))->handle();

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL")
            && $request['manageAcl'] === '1';
    });
});

it('disables ACL management when demoted to member', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $user = User::factory()->create();

    (new UpdateUserGroupLevelJob($group, $user, GroupUserLevel::Member, GroupUserLevel::Admin))->handle();

    Http::assertSent(function ($request) use ($group, $user) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL")
            && $request['mappingId'] === $user->hashid
            && $request['manageAcl'] === '0';
    });
});

it('disables ACL management when demoted to moderator', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $user = User::factory()->create();

    (new UpdateUserGroupLevelJob($group, $user, GroupUserLevel::Moderator, GroupUserLevel::Owner))->handle();

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL")
            && $request['manageAcl'] === '0';
    });
});
