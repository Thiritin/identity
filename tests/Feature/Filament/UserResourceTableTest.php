<?php

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('displays 2FA column as true when user has two factors', function () {
    $user = User::factory()->create();
    $user->twoFactors()->create(['type' => 'totp', 'secret' => 'test']);

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$user]);
});

it('displays 2FA column as false when user has no two factors', function () {
    $user = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$user]);
});

it('displays suspended column for suspended users', function () {
    $user = User::factory()->suspended()->create();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords([$user]);
});

it('can filter by 2FA status', function () {
    $userWith2fa = User::factory()->create();
    $userWith2fa->twoFactors()->create(['type' => 'totp', 'secret' => 'test']);
    $userWithout2fa = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->filterTable('has_two_factor', true)
        ->assertCanSeeTableRecords([$userWith2fa])
        ->assertCanNotSeeTableRecords([$userWithout2fa]);
});

it('can filter by suspended status', function () {
    $suspended = User::factory()->suspended()->create();
    $active = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->filterTable('suspended', true)
        ->assertCanSeeTableRecords([$suspended])
        ->assertCanNotSeeTableRecords([$active]);
});
