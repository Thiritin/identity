<?php

use App\Models\Convention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('returns all conventions ordered by year', function () {
    Convention::factory()->create(['name' => 'EF 2020', 'year' => 2020, 'start_date' => '2020-08-19', 'end_date' => '2020-08-23']);
    Convention::factory()->create(['name' => 'EF 2019', 'year' => 2019, 'start_date' => '2019-08-14', 'end_date' => '2019-08-18']);
    Convention::factory()->create(['name' => 'EF 2021', 'year' => 2021, 'start_date' => '2021-08-18', 'end_date' => '2021-08-22']);

    $response = $this->getJson('/api/v2/conventions');

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

    $response = $this->getJson('/api/v2/conventions');

    $response->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Eurofurence 28')
        ->assertJsonPath('data.0.year', 2024)
        ->assertJsonPath('data.0.start_date', '2024-09-18')
        ->assertJsonPath('data.0.end_date', '2024-09-21')
        ->assertJsonPath('data.0.theme', 'Cyberpunk');
});

it('returns the current convention without data wrapping', function () {
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

    $response = $this->getJson('/api/v2/conventions/current');

    $response->assertSuccessful()
        ->assertJsonPath('name', 'Current Convention')
        ->assertJsonMissing(['data']);
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

    $response = $this->getJson('/api/v2/conventions/current');

    $response->assertSuccessful()
        ->assertJsonPath('name', 'Future Convention');
});

it('returns 404 when no current or upcoming convention exists', function () {
    Convention::factory()->create([
        'name' => 'Past Convention',
        'year' => 2020,
        'start_date' => '2020-08-01',
        'end_date' => '2020-08-05',
    ]);

    $response = $this->getJson('/api/v2/conventions/current');

    $response->assertNotFound();
});

it('does not require authentication', function () {
    $response = $this->getJson('/api/v2/conventions');

    $response->assertSuccessful();
});

it('includes all new website fields in the list response', function () {
    Storage::fake('s3');

    Convention::factory()->create([
        'name' => 'Eurofurence 29',
        'year' => 2025,
        'start_date' => '2025-09-03',
        'end_date' => '2025-09-06',
        'theme' => 'Space Expedition',
        'location' => 'Hamburg, Germany',
        'website_url' => 'https://archive.eurofurence.org/EF29/',
        'conbook_url' => '/conbooks/EF_29_Conbook.pdf',
        'attendees_count' => 6451,
        'background_image_path' => 'conventions/backgrounds/ef29.jpg',
        'dailies' => [['title' => 'Issue 67', 'url' => '/daily/67.pdf']],
        'videos' => [['title' => 'Opening', 'url' => 'https://youtu.be/x']],
        'photos' => [['title' => 'Group', 'url' => 'https://p/1.jpg', 'thumb' => 'https://p/1.thumb.jpg']],
    ]);

    $response = $this->getJson('/api/v2/conventions');

    $response->assertSuccessful()
        ->assertJsonPath('data.0.location', 'Hamburg, Germany')
        ->assertJsonPath('data.0.website_url', 'https://archive.eurofurence.org/EF29/')
        ->assertJsonPath('data.0.conbook_url', '/conbooks/EF_29_Conbook.pdf')
        ->assertJsonPath('data.0.attendees_count', 6451)
        ->assertJsonPath('data.0.dailies.0.title', 'Issue 67')
        ->assertJsonPath('data.0.videos.0.title', 'Opening')
        ->assertJsonPath('data.0.photos.0.thumb', 'https://p/1.thumb.jpg');

    expect($response->json('data.0.background_image_url'))->toContain('conventions/backgrounds/ef29.jpg');
});

it('returns null/empty defaults for new fields when unset', function () {
    Convention::factory()->create([
        'name' => 'Bare Convention',
        'year' => 2024,
        'start_date' => '2024-08-01',
        'end_date' => '2024-08-05',
    ]);

    $response = $this->getJson('/api/v2/conventions');

    $response->assertSuccessful()
        ->assertJsonPath('data.0.location', null)
        ->assertJsonPath('data.0.website_url', null)
        ->assertJsonPath('data.0.attendees_count', null)
        ->assertJsonPath('data.0.background_image_url', null)
        ->assertJsonPath('data.0.dailies', [])
        ->assertJsonPath('data.0.videos', [])
        ->assertJsonPath('data.0.photos', []);
});

it('includes new fields on the current endpoint', function () {
    Convention::factory()->create([
        'name' => 'Current EF',
        'year' => now()->year,
        'start_date' => now()->subDay(),
        'end_date' => now()->addDays(3),
        'location' => 'Hamburg',
        'attendees_count' => 6500,
    ]);

    $response = $this->getJson('/api/v2/conventions/current');

    $response->assertSuccessful()
        ->assertJsonPath('location', 'Hamburg')
        ->assertJsonPath('attendees_count', 6500)
        ->assertJsonPath('dailies', []);
});
