<?php

use App\Events\GroupCreated;
use App\Events\GroupDeleted;
use App\Events\GroupUpdated;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('dispatches GroupCreated when group is created', function () {
    Event::fake([GroupCreated::class]);

    $group = Group::factory()->create();

    Event::assertDispatched(GroupCreated::class, function ($event) use ($group) {
        return $event->group->id === $group->id;
    });
});

it('dispatches GroupUpdated when group name changes', function () {
    $group = Group::factory()->create();

    Event::fake([GroupUpdated::class]);

    $group->update(['name' => 'New Name']);

    Event::assertDispatched(GroupUpdated::class, function ($event) use ($group) {
        return $event->group->id === $group->id
            && in_array('name', $event->changedFields);
    });
});

it('dispatches GroupUpdated when nextcloud_folder_name changes', function () {
    $group = Group::factory()->create();

    Event::fake([GroupUpdated::class]);

    $group->update(['nextcloud_folder_name' => 'New Folder']);

    Event::assertDispatched(GroupUpdated::class, function ($event) {
        return in_array('nextcloud_folder_name', $event->changedFields);
    });
});

it('does not dispatch GroupUpdated when unrelated field changes', function () {
    $group = Group::factory()->create();

    Event::fake([GroupUpdated::class]);

    $group->update(['description' => 'Updated description']);

    Event::assertNotDispatched(GroupUpdated::class);
});

it('dispatches GroupDeleted when group is deleted', function () {
    $group = Group::factory()->create();
    $hashid = $group->hashid;
    $id = $group->id;

    Event::fake([GroupDeleted::class]);

    $group->delete();

    Event::assertDispatched(GroupDeleted::class, function ($event) use ($hashid, $id) {
        return $event->groupHashid === $hashid && $event->groupId === $id;
    });
});
