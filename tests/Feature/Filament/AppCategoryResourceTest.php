<?php

use App\Filament\Resources\AppCategoryResource\Pages\CreateAppCategory;
use App\Filament\Resources\AppCategoryResource\Pages\EditAppCategory;
use App\Filament\Resources\AppCategoryResource\Pages\ListAppCategories;
use App\Models\AppCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('can list app categories', function () {
    $categories = AppCategory::factory()->count(3)->create();

    Livewire::test(ListAppCategories::class)
        ->assertCanSeeTableRecords($categories);
});

test('can create an app category', function () {
    Livewire::test(CreateAppCategory::class)
        ->fillForm([
            'name' => 'Communication',
            'sort_order' => 5,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('app_categories', [
        'name' => 'Communication',
        'sort_order' => 5,
    ]);
});

test('can edit an app category', function () {
    $category = AppCategory::factory()->create(['name' => 'Old Name']);

    Livewire::test(EditAppCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm(['name' => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($category->refresh()->name)->toBe('New Name');
});

test('category name must be unique', function () {
    AppCategory::factory()->create(['name' => 'Existing']);

    Livewire::test(CreateAppCategory::class)
        ->fillForm(['name' => 'Existing', 'sort_order' => 0])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});
