<?php

use App\Models\App;
use App\Models\User;
use Database\Seeders\AppSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function fakeHydraForSeeder(): void
{
    Http::fake([
        '*/admin/clients' => Http::sequence()
            ->push(['client_id' => 'seed-portal-' . uniqid(), 'client_secret' => 'secret'])
            ->push(['client_id' => 'seed-admin-' . uniqid(), 'client_secret' => 'secret'])
            ->push(['client_id' => 'seed-staff-' . uniqid(), 'client_secret' => 'secret']),
        '*/admin/clients/*' => Http::response(['client_id' => 'seed-id']),
    ]);
}

it('seeds system apps as approved first-party apps with skip_consent', function () {
    User::factory()->create();
    fakeHydraForSeeder();

    $this->seed(AppSeeder::class);

    foreach (['portal', 'admin', 'staff'] as $systemName) {
        $app = App::where('system_name', $systemName)->first();

        expect($app)->not->toBeNull()
            ->and($app->approved)->toBeTrue()
            ->and($app->first_party)->toBeTrue()
            ->and($app->skip_consent)->toBeTrue();
    }
});

it('updates flags on existing system apps when re-seeded', function () {
    $owner = User::factory()->create();

    App::withoutEvents(fn () => App::factory()->unapproved()->create([
        'system_name' => 'portal',
        'client_id' => 'portal-existing',
        'user_id' => $owner->id,
        'skip_consent' => false,
        'first_party' => false,
    ]));

    fakeHydraForSeeder();

    $this->seed(AppSeeder::class);

    $app = App::where('system_name', 'portal')->first();

    expect($app->approved)->toBeTrue()
        ->and($app->first_party)->toBeTrue()
        ->and($app->skip_consent)->toBeTrue();
});
