<?php

use App\Models\Convention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('shares null backgroundImageUrl when no current convention', function () {
    Convention::factory()->create(['start_date' => '2020-01-01', 'end_date' => '2020-01-05']);

    $response = $this->get('/auth/forgot-password');

    $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->where('backgroundImageUrl', null));
});

it('shares null when current convention has no background set', function () {
    Convention::factory()->create([
        'start_date' => now()->subDay(),
        'end_date' => now()->addDays(3),
        'background_image_path' => null,
    ]);

    $this->get('/auth/forgot-password')
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->where('backgroundImageUrl', null));
});

it('shares the current convention background url when set', function () {
    Storage::fake('s3');

    Convention::factory()->create([
        'start_date' => now()->subDay(),
        'end_date' => now()->addDays(3),
        'background_image_path' => 'conventions/backgrounds/ef30.jpg',
    ]);

    $this->get('/auth/forgot-password')
        ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->where(
            'backgroundImageUrl',
            fn ($url) => is_string($url) && str_contains($url, 'conventions/backgrounds/ef30.jpg'),
        ));
});
