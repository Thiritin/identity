<?php

use App\Enums\GroupTypeEnum;
use App\Jobs\Nextcloud\RemoveUserFromGroupJob;
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

it('removes user from group', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test Folder',
        'nextcloud_folder_id' => 1,
        'type' => GroupTypeEnum::Department,
    ]);
    $user = User::factory()->create();

    (new RemoveUserFromGroupJob($group, $user, GroupTypeEnum::Department))->handle();

    Http::assertSent(function ($request) use ($user) {
        return str_contains($request->url(), "ocs/v2.php/cloud/users/{$user->hashid}/groups")
            && $request->method() === 'DELETE';
    });
});

it('removes ACL for non-team groups', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test Folder',
        'nextcloud_folder_id' => 42,
        'type' => GroupTypeEnum::Department,
    ]);
    $user = User::factory()->create();

    (new RemoveUserFromGroupJob($group, $user, GroupTypeEnum::Department))->handle();

    Http::assertSent(function ($request) use ($group, $user) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL")
            && $request['mappingId'] === $user->hashid
            && $request['manageAcl'] === '0';
    });
});

it('does not remove ACL for team groups', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Team Folder',
        'nextcloud_folder_id' => 99,
        'type' => GroupTypeEnum::Team,
    ]);
    $user = User::factory()->create();

    (new RemoveUserFromGroupJob($group, $user, GroupTypeEnum::Team))->handle();

    Http::assertNotSent(function ($request) use ($group) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/manageACL");
    });
});

it('handles user not found in Nextcloud gracefully', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>404</statuscode></meta><data></data></ocs>', 404),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Test Folder',
        'nextcloud_folder_id' => 1,
        'type' => GroupTypeEnum::Department,
    ]);
    $user = User::factory()->create();

    (new RemoveUserFromGroupJob($group, $user, GroupTypeEnum::Department))->handle();

    Http::assertNotSent(function ($request) use ($user) {
        return str_contains($request->url(), "ocs/v2.php/cloud/users/{$user->hashid}/groups")
            && $request->method() === 'DELETE';
    });
});
