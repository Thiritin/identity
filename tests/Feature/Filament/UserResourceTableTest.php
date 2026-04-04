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

it('renders the users list page', function () {
    Livewire::test(ListUsers::class)
        ->assertOk();
});

it('has 2FA and suspended columns configured', function () {
    Livewire::test(ListUsers::class)
        ->assertTableColumnExists('two_factor_enabled')
        ->assertTableColumnExists('suspended_at');
});

it('can filter by 2FA status', function () {
    $userWith2fa = User::factory()->create();
    $userWith2fa->twoFactors()->create(['type' => 'totp', 'secret' => 'test']);
    $userWithout2fa = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->filterTable('has_two_factor', true)
        ->searchTable($userWith2fa->name)
        ->assertCanSeeTableRecords([$userWith2fa])
        ->assertCanNotSeeTableRecords([$userWithout2fa]);
});

it('can filter by suspended status', function () {
    $suspended = User::factory()->suspended()->create();
    $active = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->filterTable('suspended', true)
        ->assertCanSeeTableRecords([$suspended])
        ->assertCanNotSeeTableRecords([$active]);
});
