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
            ->push(['client_id' => 'seed-identity-' . uniqid(), 'client_secret' => 'secret']),
        '*/admin/clients/*' => Http::response(['client_id' => 'seed-id']),
    ]);
}

it('seeds the identity app as an approved first-party app with skip_consent', function () {
    User::factory()->create();
    fakeHydraForSeeder();

    $this->seed(AppSeeder::class);

    $app = App::where('system_name', 'identity')->first();

    expect($app)->not->toBeNull()
        ->and($app->approved)->toBeTrue()
        ->and($app->first_party)->toBeTrue()
        ->and($app->skip_consent)->toBeTrue();
});

it('updates flags on an existing identity app when re-seeded', function () {
    $owner = User::factory()->create();

    App::withoutEvents(fn () => App::factory()->unapproved()->create([
        'system_name' => 'identity',
        'client_id' => 'identity-existing',
        'user_id' => $owner->id,
        'skip_consent' => false,
        'first_party' => false,
    ]));

    fakeHydraForSeeder();

    $this->seed(AppSeeder::class);

    $app = App::where('system_name', 'identity')->first();

    expect($app->approved)->toBeTrue()
        ->and($app->first_party)->toBeTrue()
        ->and($app->skip_consent)->toBeTrue();
});

it('removes legacy portal/admin/staff apps on seed', function () {
    $owner = User::factory()->create();

    App::withoutEvents(function () use ($owner) {
        foreach (['portal', 'admin', 'staff'] as $legacy) {
            App::factory()->create([
                'system_name' => $legacy,
                'client_id' => $legacy . '-legacy',
                'user_id' => $owner->id,
            ]);
        }
    });

    fakeHydraForSeeder();

    $this->seed(AppSeeder::class);

    expect(App::whereIn('system_name', ['portal', 'admin', 'staff'])->count())->toBe(0);
    expect(App::where('system_name', 'identity')->exists())->toBeTrue();
});
