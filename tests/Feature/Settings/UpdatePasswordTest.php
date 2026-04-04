<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('sets password_changed_at when password is updated', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword1'),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->post(route('settings.update-password.store'), [
            'current_password' => 'OldPassword1',
            'password' => 'NewPassword1',
            'password_confirmation' => 'NewPassword1',
        ])
        ->assertRedirect();

    $user->refresh();
    expect($user->password_changed_at)->not->toBeNull();
});
