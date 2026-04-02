<?php

use App\Enums\GroupUserLevel;
use App\Events\GroupUserAdded;
use App\Events\GroupUserRemoved;
use App\Events\GroupUserUpdated;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('dispatches GroupUserAdded when user is added to group', function () {
    Event::fake([GroupUserAdded::class]);

    $group = Group::factory()->create();
    $user = User::factory()->create();

    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);

    Event::assertDispatched(GroupUserAdded::class, function ($event) use ($user, $group) {
        return $event->groupUser->user_id === $user->id
            && $event->groupUser->group_id === $group->id;
    });
});

it('dispatches GroupUserUpdated when user level changes', function () {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);

    Event::fake([GroupUserUpdated::class]);

    $group->users()->updateExistingPivot($user, ['level' => GroupUserLevel::Member, 'can_manage_members' => true]);

    Event::assertDispatched(GroupUserUpdated::class, function ($event) use ($user) {
        return $event->groupUser->user_id === $user->id
            && $event->oldLevel === GroupUserLevel::Member;
    });
});

it('does not dispatch GroupUserUpdated when non-level field changes', function () {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);

    Event::fake([GroupUserUpdated::class]);

    $group->users()->updateExistingPivot($user, ['title' => 'Lead']);

    Event::assertNotDispatched(GroupUserUpdated::class);
});

it('dispatches GroupUserRemoved when user is removed from group', function () {
    $group = Group::factory()->create();
    $user = User::factory()->create();
    $group->users()->attach($user, ['level' => GroupUserLevel::Member]);

    Event::fake([GroupUserRemoved::class]);

    $group->users()->detach($user);

    Event::assertDispatched(GroupUserRemoved::class, function ($event) use ($user, $group) {
        return $event->groupUser->user_id === $user->id
            && $event->groupUser->group_id === $group->id;
    });
});
