<?php

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Widgets\UserStatsWidget;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('is registered in the user resource', function () {
    expect(UserResource::getWidgets())
        ->toContain(UserStatsWidget::class);
});

it('shows correct total user count', function () {
    User::factory()->count(3)->create();

    Livewire::test(UserStatsWidget::class)
        ->assertSee('Total Users');
});

it('shows correct 2FA count', function () {
    $userWith2fa = User::factory()->create();
    $userWith2fa->twoFactors()->create(['type' => 'totp', 'secret' => 'test']);
    User::factory()->create();

    Livewire::test(UserStatsWidget::class)
        ->assertSee('2FA Enabled');
});

it('shows correct suspended count', function () {
    User::factory()->suspended()->create();
    User::factory()->create();

    Livewire::test(UserStatsWidget::class)
        ->assertSee('Suspended');
});
