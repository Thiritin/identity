<?php

use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\TwoFactor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createStaffUser(): User
{
    $user = User::factory()->create();
    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $user->groups()->attach($staffGroup, ['level' => GroupUserLevel::Member]);
    $user->twoFactors()->save(TwoFactor::factory()->totp()->make());

    return $user;
}

it('allows staff to update their profile', function () {
    $user = createStaffUser();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), [
            'firstname' => 'John',
            'lastname' => 'Doe',
            'phone' => '+49123456789',
            'credit_as' => 'JohnD',
            'spoken_languages' => ['en', 'de'],
            'visibility' => [
                'firstname' => 'all_staff',
                'lastname' => 'all_staff',
                'phone' => 'directors_only',
            ],
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->firstname)->toBe('John');
    expect($user->lastname)->toBe('Doe');
    expect($user->phone)->toBe('+49123456789');
    expect($user->credit_as)->toBe('JohnD');
    expect($user->spoken_languages)->toBe(['en', 'de']);
    expect($user->staff_profile_visibility)->toEqual([
        'firstname' => 'all_staff',
        'lastname' => 'all_staff',
        'phone' => 'directors_only',
    ]);
});

it('rejects non-staff users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), [
            'firstname' => 'John',
        ])
        ->assertForbidden();
});

it('rejects unauthenticated users', function () {
    $this->post(route('settings.staff-profile.update'), [
        'firstname' => 'John',
    ])->assertRedirect();
});

it('validates field types', function () {
    $user = createStaffUser();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), [
            'firstname' => str_repeat('a', 101),
        ])
        ->assertSessionHasErrors('firstname');
});

it('validates visibility values against enum', function () {
    $user = createStaffUser();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), [
            'visibility' => ['firstname' => 'invalid_value'],
        ])
        ->assertSessionHasErrors('visibility.firstname');
});

it('allows partial updates without clearing other fields', function () {
    $user = createStaffUser();
    $user->update(['firstname' => 'Existing', 'phone' => '+49123']);

    $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), [
            'lastname' => 'NewLast',
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->firstname)->toBe('Existing');
    expect($user->phone)->toBe('+49123');
    expect($user->lastname)->toBe('NewLast');
});

it('cannot mass-assign telegram fields', function () {
    $user = createStaffUser();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), [
            'telegram_id' => 123456789,
            'telegram_username' => 'hacker',
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->telegram_id)->toBeNull();
    expect($user->telegram_username)->toBeNull();
});

it('clears fields when null is sent', function () {
    $user = createStaffUser();
    $user->update(['firstname' => 'John', 'phone' => '+49123']);

    $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), [
            'firstname' => null,
            'phone' => null,
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->firstname)->toBeNull();
    expect($user->phone)->toBeNull();
});

it('validates birthdate is before today', function () {
    $user = createStaffUser();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), [
            'birthdate' => now()->addDay()->format('Y-m-d'),
        ])
        ->assertSessionHasErrors('birthdate');
});

it('validates spoken_languages items max length', function () {
    $user = createStaffUser();

    $this->actingAs($user)
        ->post(route('settings.staff-profile.update'), [
            'spoken_languages' => ['toolongcode'],
        ])
        ->assertSessionHasErrors('spoken_languages.0');
});
