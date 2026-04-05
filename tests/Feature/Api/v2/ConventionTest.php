<?php

use App\Models\Convention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ValidatesOpenApiV2;

uses(RefreshDatabase::class, ValidatesOpenApiV2::class);

it('returns all conventions ordered by year', function () {
    Convention::factory()->create(['name' => 'EF 2020', 'year' => 2020, 'start_date' => '2020-08-19', 'end_date' => '2020-08-23']);
    Convention::factory()->create(['name' => 'EF 2019', 'year' => 2019, 'start_date' => '2019-08-14', 'end_date' => '2019-08-18']);
    Convention::factory()->create(['name' => 'EF 2021', 'year' => 2021, 'start_date' => '2021-08-18', 'end_date' => '2021-08-22']);

    $response = $this->getJson('/api/v2/conventions');

    $response->assertSuccessful()
        ->assertJsonCount(3)
        ->assertJsonPath('0.year', 2019)
        ->assertJsonPath('1.year', 2020)
        ->assertJsonPath('2.year', 2021);

    $this->assertMatchesOpenApiV2($response, '/conventions');
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
        ->assertJsonPath('0.name', 'Eurofurence 28')
        ->assertJsonPath('0.year', 2024)
        ->assertJsonPath('0.start_date', '2024-09-18')
        ->assertJsonPath('0.end_date', '2024-09-21')
        ->assertJsonPath('0.theme', 'Cyberpunk');

    $this->assertMatchesOpenApiV2($response, '/conventions');
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

    $this->assertMatchesOpenApiV2($response, '/conventions/current');
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

    $this->assertMatchesOpenApiV2($response, '/conventions/current');
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

    $this->assertMatchesOpenApiV2($response, '/conventions/current');
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
        ->assertJsonPath('0.location', 'Hamburg, Germany')
        ->assertJsonPath('0.website_url', 'https://archive.eurofurence.org/EF29/')
        ->assertJsonPath('0.conbook_url', '/conbooks/EF_29_Conbook.pdf')
        ->assertJsonPath('0.attendees_count', 6451)
        ->assertJsonPath('0.dailies.0.title', 'Issue 67')
        ->assertJsonPath('0.videos.0.title', 'Opening')
        ->assertJsonPath('0.photos.0.thumb', 'https://p/1.thumb.jpg');

    expect($response->json('0.background_image_url'))->toContain('conventions/backgrounds/ef29.jpg');

    $this->assertMatchesOpenApiV2($response, '/conventions');
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
        ->assertJsonPath('0.location', null)
        ->assertJsonPath('0.website_url', null)
        ->assertJsonPath('0.attendees_count', null)
        ->assertJsonPath('0.background_image_url', null)
        ->assertJsonPath('0.dailies', [])
        ->assertJsonPath('0.videos', [])
        ->assertJsonPath('0.photos', []);

    $this->assertMatchesOpenApiV2($response, '/conventions');
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

    $this->assertMatchesOpenApiV2($response, '/conventions/current');
});
