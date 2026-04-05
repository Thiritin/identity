<?php

use App\Models\Convention;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('current scope returns the earliest convention whose end_date is today or later', function () {
    Convention::factory()->create(['year' => 2020, 'start_date' => '2020-08-01', 'end_date' => '2020-08-05']);
    $upcoming = Convention::factory()->create(['year' => now()->year, 'start_date' => now()->addDay(), 'end_date' => now()->addDays(5)]);
    Convention::factory()->create(['year' => now()->year + 1, 'start_date' => now()->addYear(), 'end_date' => now()->addYear()->addDays(4)]);

    expect(Convention::current()->first()?->id)->toBe($upcoming->id);
});

it('current scope returns null when all conventions are in the past', function () {
    Convention::factory()->create(['start_date' => '2020-08-01', 'end_date' => '2020-08-05']);

    expect(Convention::current()->first())->toBeNull();
});

it('background_image_url returns null when path is not set', function () {
    $convention = Convention::factory()->create(['background_image_path' => null]);

    expect($convention->background_image_url)->toBeNull();
});

it('background_image_url returns a url when path is set', function () {
    Storage::fake('s3');
    $convention = Convention::factory()->create(['background_image_path' => 'conventions/backgrounds/test.jpg']);

    expect($convention->background_image_url)->toContain('conventions/backgrounds/test.jpg');
});
