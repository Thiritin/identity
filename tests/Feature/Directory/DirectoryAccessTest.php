<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function setupStaffUser(): array
{
    $staffGroup = Group::factory()->create([
        'system_name' => 'staff',
        'type' => GroupTypeEnum::Automated,
        'name' => 'Staff',
    ]);
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $user = User::factory()->create();
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($user, ['level' => GroupUserLevel::Member]);

    return [$user, $root, $staffGroup];
}

test('non-staff users get 403 on directory index', function () {
    $user = User::factory()->create();
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $this->actingAs($user)
        ->get(route('directory.index'))
        ->assertForbidden();
});

test('staff users can view directory index', function () {
    [$user] = setupStaffUser();

    $this->actingAs($user)
        ->get(route('directory.index'))
        ->assertOk();
});

test('staff users can view group detail', function () {
    [$user, $root] = setupStaffUser();

    $this->actingAs($user)
        ->get(route('directory.show', $root->slug))
        ->assertOk();
});

test('non-staff users get 403 on group detail', function () {
    $root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $user = User::factory()->create();
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $this->actingAs($user)
        ->get(route('directory.show', $root->slug))
        ->assertForbidden();
});

test('directory routes receive directoryTree shared prop', function () {
    [$user] = setupStaffUser();

    $response = $this->actingAs($user)
        ->get(route('directory.index'));

    $response->assertOk();
    $page = $response->original->getData()['page'];
    expect($page['props'])->toHaveKey('directoryTree');
    expect($page['props'])->toHaveKey('myGroupCount');
});

test('show page sets directorySelectedSlug', function () {
    [$user, $root] = setupStaffUser();

    $response = $this->actingAs($user)
        ->get(route('directory.show', $root->slug));

    $response->assertOk();
    $page = $response->original->getData()['page'];
    expect($page['props']['directorySelectedSlug'])->toBe($root->slug);
});

test('staff users can view staff profile', function () {
    [$user, $root, $staffGroup] = setupStaffUser();
    $department = Group::factory()->department()->create(['parent_id' => $root->id]);
    $department->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $otherUser = User::factory()->create();
    $otherUser->twoFactors()->save(TwoFactor::factory()->totp()->make());
    $staffGroup->users()->attach($otherUser, ['level' => GroupUserLevel::Member]);
    $department->users()->attach($otherUser, ['level' => GroupUserLevel::Member]);

    $this->actingAs($user)
        ->get(route('directory.members.show', ['slug' => $department->slug, 'user' => $otherUser->hashid]))
        ->assertOk();
});
