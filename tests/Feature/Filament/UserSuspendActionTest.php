<?php

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake(['*/admin/oauth2/auth/sessions/login*' => Http::response(null, 204)]);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('can suspend a user', function () {
    $user = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->callAction(TestAction::make('suspend')->table($user));

    expect($user->refresh()->isSuspended())->toBeTrue();
});

it('can unsuspend a user', function () {
    $user = User::factory()->suspended()->create();

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->callAction(TestAction::make('unsuspend')->table($user));

    expect($user->refresh()->isSuspended())->toBeFalse();
});

it('hides suspend action for the current user', function () {
    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertActionHidden(TestAction::make('suspend')->table($this->admin));
});

it('hides unsuspend action for non-suspended users', function () {
    $user = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertActionHidden(TestAction::make('unsuspend')->table($user));
});

it('hides suspend action for suspended users', function () {
    $user = User::factory()->suspended()->create();

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->assertActionHidden(TestAction::make('suspend')->table($user));
});
