<?php

use App\Enums\GroupUserLevel;
use App\Models\Convention;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function staffUser(): User
{
    $user = User::factory()->create();
    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    return $user;
}

it('allows staff to add a convention to their attendance', function () {
    $user = staffUser();
    $convention = Convention::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.conventions'), [
            'action' => 'add',
            'convention_id' => $convention->id,
        ])
        ->assertRedirect();

    expect($user->conventions()->where('convention_id', $convention->id)->exists())->toBeTrue();
    $pivot = $user->conventions()->where('convention_id', $convention->id)->first()->pivot;
    expect($pivot->is_attended)->toBeTrue();
    expect($pivot->is_staff)->toBeFalse();
});

it('rejects adding a convention already linked', function () {
    $user = staffUser();
    $convention = Convention::factory()->create();
    $user->conventions()->attach($convention, ['is_attended' => true, 'is_staff' => false]);

    $this->actingAs($user)
        ->post(route('settings.staff-profile.conventions'), [
            'action' => 'add',
            'convention_id' => $convention->id,
        ])
        ->assertSessionHasErrors('convention_id');
});

it('allows staff to toggle their is_attended flag', function () {
    $user = staffUser();
    $convention = Convention::factory()->create();
    $user->conventions()->attach($convention, ['is_attended' => true, 'is_staff' => false]);

    $this->actingAs($user)
        ->post(route('settings.staff-profile.conventions'), [
            'action' => 'update',
            'convention_id' => $convention->id,
            'is_attended' => false,
        ])
        ->assertRedirect();

    $pivot = $user->conventions()->where('convention_id', $convention->id)->first()->pivot;
    expect($pivot->is_attended)->toBeFalse();
});

it('allows staff to remove a non-staff entry', function () {
    $user = staffUser();
    $convention = Convention::factory()->create();
    $user->conventions()->attach($convention, ['is_attended' => true, 'is_staff' => false]);

    $this->actingAs($user)
        ->post(route('settings.staff-profile.conventions'), [
            'action' => 'remove',
            'convention_id' => $convention->id,
        ])
        ->assertRedirect();

    expect($user->conventions()->where('convention_id', $convention->id)->exists())->toBeFalse();
});

it('blocks staff from removing a staff entry', function () {
    $user = staffUser();
    $convention = Convention::factory()->create();
    $user->conventions()->attach($convention, ['is_attended' => true, 'is_staff' => true]);

    $this->actingAs($user)
        ->post(route('settings.staff-profile.conventions'), [
            'action' => 'remove',
            'convention_id' => $convention->id,
        ])
        ->assertForbidden();

    expect($user->conventions()->where('convention_id', $convention->id)->exists())->toBeTrue();
});

it('prevents staff from setting is_staff on themselves', function () {
    $user = staffUser();
    $convention = Convention::factory()->create();
    $user->conventions()->attach($convention, ['is_attended' => true, 'is_staff' => false]);

    $this->actingAs($user)
        ->post(route('settings.staff-profile.conventions'), [
            'action' => 'update',
            'convention_id' => $convention->id,
            'is_staff' => true,
        ])
        ->assertRedirect();

    $pivot = $user->conventions()->where('convention_id', $convention->id)->first()->pivot;
    expect($pivot->is_staff)->toBeFalse();
});

it('rejects non-staff users', function () {
    $user = User::factory()->create();
    $convention = Convention::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.conventions'), [
            'action' => 'add',
            'convention_id' => $convention->id,
        ])
        ->assertForbidden();
});
