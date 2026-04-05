<?php

use App\Filament\Convention\Resources\ConventionResource\Pages\CreateConvention;
use App\Filament\Convention\Resources\ConventionResource\Pages\EditConvention;
use App\Models\Convention;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->manager = User::factory()->create([
        'is_admin' => false,
        'is_convention_manager' => true,
    ]);
    $this->actingAs($this->manager);
    Filament::setCurrentPanel(Filament::getPanel('convention'));
});

it('creates a convention with scalar fields', function () {
    Livewire::test(CreateConvention::class)
        ->fillForm([
            'name' => 'EF 30',
            'year' => 2026,
            'theme' => 'Test',
            'start_date' => '2026-08-19',
            'end_date' => '2026-08-23',
            'location' => 'Hamburg, Germany',
            'attendees_count' => 7000,
            'website_url' => 'https://example.com',
            'dailies' => [],
            'videos' => [],
            'photos' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Convention::where('name', 'EF 30')->exists())->toBeTrue();
});

it('saves repeater rows for dailies, videos, photos', function () {
    $convention = Convention::factory()->create();

    Livewire::test(EditConvention::class, ['record' => $convention->getRouteKey()])
        ->fillForm([
            'dailies' => [['title' => 'Issue 1', 'url' => '/daily/1.pdf']],
            'videos' => [['title' => 'Opening', 'url' => 'https://youtu.be/x']],
            'photos' => [['title' => 'Group', 'url' => 'https://p/1.jpg', 'thumb' => 'https://p/1.thumb.jpg']],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $convention->refresh();
    expect($convention->dailies)->toHaveCount(1);
    expect($convention->videos[0]['title'])->toBe('Opening');
    expect($convention->photos[0]['thumb'])->toBe('https://p/1.thumb.jpg');
});

it('uploads a background image to the s3 disk', function () {
    Storage::fake('s3');
    $convention = Convention::factory()->create();

    Livewire::test(EditConvention::class, ['record' => $convention->getRouteKey()])
        ->fillForm([
            'background_image_path' => [File::image('bg.jpg', 1200, 800)],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $convention->refresh();
    expect($convention->background_image_path)->toStartWith('conventions/backgrounds/');
    Storage::disk('s3')->assertExists($convention->background_image_path);
});
