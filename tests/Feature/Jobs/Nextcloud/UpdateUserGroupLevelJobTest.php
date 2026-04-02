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

it('enables ACL management when promoted to team lead', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $user = User::factory()->create();

    (new UpdateUserGroupLevelJob($group, $user, GroupUserLevel::TeamLead, GroupUserLevel::Member))->handle();

    Http::assertSent(function ($request) use ($group, $user) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL")
            && $request['mappingId'] === $user->hashid
            && $request['mappingType'] === 'user'
            && $request['manageAcl'] === '1';
    });
});

it('enables ACL management when member gets manager flag', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $user = User::factory()->create();

    (new UpdateUserGroupLevelJob($group, $user, GroupUserLevel::Member, GroupUserLevel::Member, true))->handle();

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

    (new UpdateUserGroupLevelJob($group, $user, GroupUserLevel::Member, GroupUserLevel::TeamLead))->handle();

    Http::assertSent(function ($request) use ($group, $user) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL")
            && $request['mappingId'] === $user->hashid
            && $request['manageAcl'] === '0';
    });
});

it('disables ACL management when manager flag is removed', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create(['nextcloud_folder_id' => 42]);
    $user = User::factory()->create();

    (new UpdateUserGroupLevelJob($group, $user, GroupUserLevel::Member, GroupUserLevel::Member, false))->handle();

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL")
            && $request['manageAcl'] === '0';
    });
});
