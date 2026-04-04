<?php

use App\Models\Convention;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns all conventions ordered by year', function () {
    Convention::factory()->create(['name' => 'EF 2020', 'year' => 2020, 'start_date' => '2020-08-19', 'end_date' => '2020-08-23']);
    Convention::factory()->create(['name' => 'EF 2019', 'year' => 2019, 'start_date' => '2019-08-14', 'end_date' => '2019-08-18']);
    Convention::factory()->create(['name' => 'EF 2021', 'year' => 2021, 'start_date' => '2021-08-18', 'end_date' => '2021-08-22']);

    $response = $this->getJson('/api/v1/conventions');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.year', 2019)
        ->assertJsonPath('data.1.year', 2020)
        ->assertJsonPath('data.2.year', 2021);
});

it('returns convention with all expected fields', function () {
    Convention::factory()->create([
        'name' => 'Eurofurence 28',
        'year' => 2024,
        'start_date' => '2024-09-18',
        'end_date' => '2024-09-21',
        'theme' => 'Cyberpunk',
    ]);

    $response = $this->getJson('/api/v1/conventions');

    $response->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Eurofurence 28')
        ->assertJsonPath('data.0.year', 2024)
        ->assertJsonPath('data.0.start_date', '2024-09-18')
        ->assertJsonPath('data.0.end_date', '2024-09-21')
        ->assertJsonPath('data.0.theme', 'Cyberpunk');
});

it('returns the current convention', function () {
    Convention::factory()->create([
        'name' => 'Past Convention',
        'year' => 2020,
        'start_date' => '2020-08-01',
        'end_date' => '2020-08-05',
    ]);

    Convention::factory()->create([
        'name' => 'Current Convention',
        'year' => now()->year,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDays(3)->toDateString(),
    ]);

    $response = $this->getJson('/api/v1/conventions/current');

    $response->assertSuccessful()
        ->assertJsonPath('data.name', 'Current Convention');
});

it('returns the next upcoming convention when none is currently running', function () {
    Convention::factory()->create([
        'name' => 'Past Convention',
        'year' => 2020,
        'start_date' => '2020-08-01',
        'end_date' => '2020-08-05',
    ]);

    Convention::factory()->create([
        'name' => 'Future Convention',
        'year' => now()->addYear()->year,
        'start_date' => now()->addYear()->toDateString(),
        'end_date' => now()->addYear()->addDays(4)->toDateString(),
    ]);

    $response = $this->getJson('/api/v1/conventions/current');

    $response->assertSuccessful()
        ->assertJsonPath('data.name', 'Future Convention');
});

it('returns 404 when no current or upcoming convention exists', function () {
    Convention::factory()->create([
        'name' => 'Past Convention',
        'year' => 2020,
        'start_date' => '2020-08-01',
        'end_date' => '2020-08-05',
    ]);

    $response = $this->getJson('/api/v1/conventions/current');

    $response->assertNotFound();
});

it('does not require authentication', function () {
    $response = $this->getJson('/api/v1/conventions');

    $response->assertSuccessful();
});
