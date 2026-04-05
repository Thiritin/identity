<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests to convention login', function () {
    $this->get('/convention')->assertRedirect('/convention/login');
});

it('denies users without any flag', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'is_convention_manager' => false,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('convention'));
    $this->actingAs($user)->get('/convention')->assertForbidden();
});

it('allows convention managers', function () {
    $user = User::factory()->create([
        'is_admin' => false,
        'is_convention_manager' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('convention'));
    $this->actingAs($user)->get('/convention')->assertSuccessful();
});

it('allows admins via bypass', function () {
    $user = User::factory()->create([
        'is_admin' => true,
        'is_convention_manager' => false,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('convention'));
    $this->actingAs($user)->get('/convention')->assertSuccessful();
});

it('denies suspended convention managers', function () {
    $user = User::factory()->suspended()->create([
        'is_convention_manager' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('convention'));
    $this->actingAs($user)->get('/convention')->assertForbidden();
});

it('denies suspended admins on the convention panel', function () {
    $user = User::factory()->suspended()->create([
        'is_admin' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('convention'));
    $this->actingAs($user)->get('/convention')->assertForbidden();
});
