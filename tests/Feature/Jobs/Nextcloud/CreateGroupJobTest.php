<?php

use App\Jobs\Nextcloud\CreateGroupJob;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.nextcloud.baseUrl', 'https://cloud.example.com');
    config()->set('services.nextcloud.username', 'admin');
    config()->set('services.nextcloud.password', 'secret');
});

it('creates group and sets display name', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $group = Group::factory()->create(['name' => 'My Group']);

    (new CreateGroupJob($group))->handle();

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), 'ocs/v1.php/cloud/groups')
            && $request->method() === 'POST'
            && $request['groupid'] === $group->hashid;
    });

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), "ocs/v2.php/cloud/groups/{$group->hashid}")
            && $request->method() === 'PUT'
            && $request['key'] === 'displayname'
            && $request['value'] === $group->name;
    });
});

it('adds team group to parent folder with combined display name', function () {
    Http::fake([
        '*' => Http::response('<ocs><meta><statuscode>200</statuscode></meta><data></data></ocs>', 200),
    ]);

    $parent = Group::factory()->create(['name' => 'Parent Group']);
    $group = Group::factory()->create(['name' => 'Team Alpha', 'parent_id' => $parent->id]);
    $parentFolderId = 55;

    // Reload the group with parent relationship eager loaded
    $group->load('parent');

    (new CreateGroupJob($group, true, $parentFolderId))->handle();

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), 'ocs/v1.php/cloud/groups')
            && $request->method() === 'POST'
            && $request['groupid'] === $group->hashid;
    });

    Http::assertSent(function ($request) use ($group) {
        return str_contains($request->url(), "ocs/v2.php/cloud/groups/{$group->hashid}")
            && $request->method() === 'PUT'
            && $request['key'] === 'displayname'
            && $request['value'] === $group->parent->name . ' / ' . $group->name;
    });

    Http::assertSent(function ($request) use ($parentFolderId, $group) {
        return str_contains($request->url(), "apps/groupfolders/folders/{$parentFolderId}/groups")
            && $request->method() === 'POST'
            && $request['group'] === $group->hashid;
    });
});
