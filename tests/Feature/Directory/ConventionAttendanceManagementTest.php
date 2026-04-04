<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Convention;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function directorAndPeer(): array
{
    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $director = User::factory()->create();
    $director->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $director->groups()->attach($department, ['level' => GroupUserLevel::Director]);
    $director->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $peer = User::factory()->create();
    $peer->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $peer->groups()->attach($department, ['level' => GroupUserLevel::Member]);
    $peer->twoFactors()->save(TwoFactor::factory()->totp()->make());

    return [$director, $peer, $department];
}

it('allows director to add convention for peer', function () {
    [$director, $peer, $department] = directorAndPeer();
    $convention = Convention::factory()->create();

    $this->actingAs($director)
        ->post(route('directory.members.conventions', ['slug' => $department->slug, 'user' => $peer->hashid]), [
            'action' => 'add',
            'convention_id' => $convention->id,
            'is_attended' => true,
            'is_staff' => true,
        ])
        ->assertRedirect();

    $pivot = $peer->conventions()->where('convention_id', $convention->id)->first()->pivot;
    expect($pivot->is_attended)->toBeTrue();
    expect($pivot->is_staff)->toBeTrue();
});

it('allows director to toggle is_staff for peer', function () {
    [$director, $peer, $department] = directorAndPeer();
    $convention = Convention::factory()->create();
    $peer->conventions()->attach($convention, ['is_attended' => true, 'is_staff' => false]);

    $this->actingAs($director)
        ->post(route('directory.members.conventions', ['slug' => $department->slug, 'user' => $peer->hashid]), [
            'action' => 'update',
            'convention_id' => $convention->id,
            'is_attended' => true,
            'is_staff' => true,
        ])
        ->assertRedirect();

    $pivot = $peer->fresh()->conventions()->where('convention_id', $convention->id)->first()->pivot;
    expect($pivot->is_staff)->toBeTrue();
});

it('allows director to remove any entry for peer', function () {
    [$director, $peer, $department] = directorAndPeer();
    $convention = Convention::factory()->create();
    $peer->conventions()->attach($convention, ['is_attended' => true, 'is_staff' => true]);

    $this->actingAs($director)
        ->post(route('directory.members.conventions', ['slug' => $department->slug, 'user' => $peer->hashid]), [
            'action' => 'remove',
            'convention_id' => $convention->id,
        ])
        ->assertRedirect();

    expect($peer->conventions()->where('convention_id', $convention->id)->exists())->toBeFalse();
});

it('blocks user without can_manage_members in shared group', function () {
    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $department = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $viewer = User::factory()->create();
    $viewer->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $viewer->groups()->attach($department, ['level' => GroupUserLevel::Member]);
    $viewer->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $target = User::factory()->create();
    $target->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $target->groups()->attach($department, ['level' => GroupUserLevel::Member]);

    $convention = Convention::factory()->create();

    $this->actingAs($viewer)
        ->post(route('directory.members.conventions', ['slug' => $department->slug, 'user' => $target->hashid]), [
            'action' => 'add',
            'convention_id' => $convention->id,
        ])
        ->assertForbidden();
});

it('blocks user with can_manage_members but no shared group', function () {
    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $dept1 = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $dept2 = Group::factory()->create(['type' => GroupTypeEnum::Department]);

    $director = User::factory()->create();
    $director->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $director->groups()->attach($dept1, ['level' => GroupUserLevel::Director]);
    $director->twoFactors()->save(TwoFactor::factory()->totp()->make());

    $target = User::factory()->create();
    $target->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $target->groups()->attach($dept2, ['level' => GroupUserLevel::Member]);

    $convention = Convention::factory()->create();

    $this->actingAs($director)
        ->post(route('directory.members.conventions', ['slug' => $dept1->slug, 'user' => $target->hashid]), [
            'action' => 'add',
            'convention_id' => $convention->id,
        ])
        ->assertForbidden();
});
