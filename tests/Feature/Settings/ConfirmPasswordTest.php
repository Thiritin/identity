<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('shares passwordConfirmRequired prop on GET when password is not confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('settings.security.password'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('passwordConfirmRequired', true)
        );
});

it('does not share passwordConfirmRequired prop when password is confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->get(route('settings.security.password'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->missing('passwordConfirmRequired')
        );
});

it('returns 423 for POST requests when password is not confirmed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('settings.update-password.store'), [
            'current_password' => 'password',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])
        ->assertStatus(423);
});

it('allows POST requests when password is confirmed', function () {
    $user = User::factory()->create([
        'password' => Hash::make('known-password'),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->post(route('settings.update-password.store'), [
            'current_password' => 'known-password',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])
        ->assertSessionHasNoErrors();
});

it('confirms password successfully', function () {
    $user = User::factory()->create([
        'password' => Hash::make('known-password'),
    ]);

    $this->actingAs($user)
        ->post(route('settings.security.confirm-password'), [
            'password' => 'known-password',
        ])
        ->assertSessionHasNoErrors();

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

it('rejects wrong password on confirm', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('settings.security.confirm-password'), [
            'password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('password');
});

it('throttles confirm password attempts', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('settings.security.confirm-password'), [
            'password' => 'wrong-password',
        ]);
    }

    $this->post(route('settings.security.confirm-password'), [
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('password');
});
