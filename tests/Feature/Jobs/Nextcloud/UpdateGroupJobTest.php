<?php

use App\Enums\GroupUserLevel;
use App\Jobs\Nextcloud\UpdateGroupJob;
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

it('renames folder when nextcloud_folder_name changes and folder exists', function () {
    Http::fake([
        'cloud.example.com/apps/groupfolders/folders/*/mountpoint' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'New Folder Name',
        'nextcloud_folder_id' => 7,
    ]);

    (new UpdateGroupJob($group, ['nextcloud_folder_name' => 'Old Folder Name'], ['nextcloud_folder_name']))->handle();

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$group->nextcloud_folder_id}/mountpoint")
            && $request->method() === 'POST'
            && $request['mountpoint'] === 'New Folder Name';
    });
});

it('creates folder and adds all users when folder is new', function () {
    Http::fake([
        'cloud.example.com/ocs/v1.php/cloud/groups' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
        'cloud.example.com/apps/groupfolders/folders' => Http::response('<ocs><data><id>10</id></data></ocs>', 200),
        'cloud.example.com/apps/groupfolders/folders/*/acl' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
        'cloud.example.com/apps/groupfolders/folders/*/groups' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
        'cloud.eurofurence.org/ocs/v2.php/cloud/groups/*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
        'cloud.example.com/ocs/v2.php/cloud/users/*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
        'cloud.example.com/apps/groupfolders/folders/*/manageACL' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create([
        'nextcloud_folder_name' => 'Brand New Folder',
        'nextcloud_folder_id' => null,
    ]);

    $memberUser = User::factory()->create();
    $adminUser = User::factory()->create();

    $group->users()->attach($memberUser, ['level' => GroupUserLevel::Member]);
    $group->users()->attach($adminUser, ['level' => GroupUserLevel::Admin]);

    (new UpdateGroupJob($group, ['nextcloud_folder_name' => null], ['nextcloud_folder_name']))->handle();

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), 'ocs/v1.php/cloud/groups')
            && $request->method() === 'POST'
            && $request['groupid'] === $group->hashid;
    });

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'apps/groupfolders/folders')
            && $request->method() === 'POST'
            && isset($request['mountpoint']);
    });

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), "ocs/v2.php/cloud/groups/{$group->hashid}")
            && $request->method() === 'PUT'
            && $request['key'] === 'displayname';
    });

    // Admin user should have ACL set
    Http::assertSent(function ($request) use ($adminUser) {
        return str_contains($request->url(), 'manageACL')
            && $request['mappingId'] === $adminUser->hashid
            && $request['manageAcl'] === '1';
    });

    // Member user should NOT have ACL set
    Http::assertNotSent(function ($request) use ($memberUser) {
        return str_contains($request->url(), 'manageACL')
            && $request['mappingId'] === $memberUser->hashid;
    });

    // Folder ID should be saved on the group
    expect($group->fresh()->nextcloud_folder_id)->toBe(10);
});
