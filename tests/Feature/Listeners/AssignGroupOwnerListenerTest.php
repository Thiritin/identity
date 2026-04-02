<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Events\GroupCreated;
use App\Listeners\AssignGroupOwner;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

it('assigns authenticated user as owner of new group', function () {
    $user = User::factory()->create();

    $group = Group::withoutEvents(fn () => Group::factory()->create());

    Auth::shouldReceive('user')->andReturn($user);

    $listener = new AssignGroupOwner();
    $listener->handle(new GroupCreated($group));

    expect($group->users()->where('user_id', $user->id)->wherePivot('level', GroupUserLevel::Owner)->exists())->toBeTrue();
});

it('does not assign owner when no authenticated user', function () {
    $group = Group::withoutEvents(fn () => Group::factory()->create());

    Auth::shouldReceive('user')->andReturn(null);

    $listener = new AssignGroupOwner();
    $listener->handle(new GroupCreated($group));

    expect($group->users()->count())->toBe(0);
});

it('does not assign owner for team group with nextcloud parent', function () {
    $user = User::factory()->create();

    $parent = Group::withoutEvents(fn () => Group::factory()->create(['nextcloud_folder_id' => 42]));
    $group = Group::withoutEvents(fn () => Group::factory()->create([
        'type' => GroupTypeEnum::Team,
        'parent_id' => $parent->id,
    ]))->refresh();

    Auth::shouldReceive('user')->andReturn($user);

    $listener = new AssignGroupOwner();
    $listener->handle(new GroupCreated($group));

    expect($group->users()->where('user_id', $user->id)->exists())->toBeFalse();
});
