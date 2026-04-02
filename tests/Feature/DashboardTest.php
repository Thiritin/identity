<?php

use App\Models\App;
use App\Models\AppCategory;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    App::flushEventListeners();
});

test('dashboard returns pinned, categories, and uncategorized structure', function () {
    $user = User::factory()->create();

    $category = AppCategory::factory()->create(['name' => 'Communication', 'sort_order' => 1]);

    $pinnedApp = App::factory()->public()->pinned()->withCategory($category)->create(['name' => 'Pinned App', 'priority' => 1]);
    $categorizedApp = App::factory()->public()->withCategory($category)->create(['name' => 'Category App', 'priority' => 2]);
    $uncategorizedApp = App::factory()->public()->create(['name' => 'Uncategorized App', 'priority' => 3]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('pinned', 1)
            ->where('pinned.0.name', 'Pinned App')
            ->has('categories', 1)
            ->where('categories.0.name', 'Communication')
            ->has('categories.0.apps', 1)
            ->where('categories.0.apps.0.name', 'Category App')
            ->has('uncategorized', 1)
            ->where('uncategorized.0.name', 'Uncategorized App')
        );
});

test('pinned apps are excluded from category sections', function () {
    $user = User::factory()->create();
    $category = AppCategory::factory()->create();

    App::factory()->public()->pinned()->withCategory($category)->create(['name' => 'Pinned']);
    App::factory()->public()->withCategory($category)->create(['name' => 'Normal']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('pinned', 1)
            ->where('pinned.0.name', 'Pinned')
            ->has('categories.0.apps', 1)
            ->where('categories.0.apps.0.name', 'Normal')
        );
});

test('empty categories are excluded', function () {
    $user = User::factory()->create();
    AppCategory::factory()->create(['name' => 'Empty Category']);
    $hasCat = AppCategory::factory()->create(['name' => 'Has Apps']);

    App::factory()->public()->withCategory($hasCat)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('categories', 1)
            ->where('categories.0.name', 'Has Apps')
        );
});

test('categories ordered by sort_order then name', function () {
    $user = User::factory()->create();

    $catB = AppCategory::factory()->create(['name' => 'Bravo', 'sort_order' => 1]);
    $catA = AppCategory::factory()->create(['name' => 'Alpha', 'sort_order' => 1]);
    $catZ = AppCategory::factory()->create(['name' => 'Zulu', 'sort_order' => 0]);

    App::factory()->public()->withCategory($catB)->create();
    App::factory()->public()->withCategory($catA)->create();
    App::factory()->public()->withCategory($catZ)->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('categories.0.name', 'Zulu')
            ->where('categories.1.name', 'Alpha')
            ->where('categories.2.name', 'Bravo')
        );
});

test('visibility rules apply to pinned apps', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();

    $restrictedApp = App::factory()->pinned()->create([
        'public' => false,
        'category_id' => null,
    ]);
    $restrictedApp->groups()->attach($group);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('pinned', 0)
        );
});

test('group-restricted non-pinned app is hidden from non-members', function () {
    $user = User::factory()->create();
    $group = Group::factory()->create();
    $category = AppCategory::factory()->create();

    $restrictedApp = App::factory()->withCategory($category)->create([
        'public' => false,
    ]);
    $restrictedApp->groups()->attach($group);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('categories', 0)
        );
});

test('apps outside date range are excluded', function () {
    $user = User::factory()->create();

    App::factory()->public()->create([
        'starts_at' => now()->addDay(),
        'name' => 'Future App',
    ]);

    App::factory()->public()->create([
        'ends_at' => now()->subDay(),
        'name' => 'Expired App',
    ]);

    App::factory()->public()->create([
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'name' => 'Active App',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('uncategorized', 1)
            ->where('uncategorized.0.name', 'Active App')
        );
});

test('apps within category ordered by priority', function () {
    $user = User::factory()->create();
    $category = AppCategory::factory()->create();

    App::factory()->public()->withCategory($category)->create(['name' => 'Second', 'priority' => 200]);
    App::factory()->public()->withCategory($category)->create(['name' => 'First', 'priority' => 100]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('categories.0.apps.0.name', 'First')
            ->where('categories.0.apps.1.name', 'Second')
        );
});

test('dashboard works with no categories and no pinned apps (transition state)', function () {
    $user = User::factory()->create();

    App::factory()->public()->create(['name' => 'Basic App']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('pinned', 0)
            ->has('categories', 0)
            ->has('uncategorized', 1)
            ->where('uncategorized.0.name', 'Basic App')
        );
});

test('app includes image_url when image is set', function () {
    $user = User::factory()->create();

    App::factory()->public()->create([
        'image' => 'app-images/test.png',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('uncategorized.0.image_url', fn ($value) => str_contains($value, 'app-images/test.png'))
        );
});

test('app image_url is null when no image', function () {
    $user = User::factory()->create();

    App::factory()->public()->create(['image' => null]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('uncategorized.0.image_url', null)
        );
});

test('registration app shown as hero when configured and active', function () {
    config(['services.registration.client_id' => 'reg-app-id']);

    $user = User::factory()->create();
    App::factory()->public()->create([
        'client_id' => 'reg-app-id',
        'name' => 'Eurofurence Registration',
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('registration.name', 'Eurofurence Registration')
            ->has('uncategorized', 0)
        );
});

test('registration app not shown when outside date range', function () {
    config(['services.registration.client_id' => 'reg-app-id']);

    $user = User::factory()->create();
    App::factory()->public()->create([
        'client_id' => 'reg-app-id',
        'name' => 'Eurofurence Registration',
        'starts_at' => now()->addMonth(),
        'ends_at' => now()->addMonths(2),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('registration', null)
        );
});

test('registration app excluded from other sections', function () {
    config(['services.registration.client_id' => 'reg-app-id']);

    $user = User::factory()->create();
    $category = AppCategory::factory()->create();

    App::factory()->public()->pinned()->withCategory($category)->create([
        'client_id' => 'reg-app-id',
        'name' => 'Registration',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('registration.name', 'Registration')
            ->has('pinned', 0)
            ->has('categories', 0)
        );
});

test('registration is null when not configured', function () {
    config(['services.registration.client_id' => null]);

    $user = User::factory()->create();
    App::factory()->public()->create(['name' => 'Some App']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('registration', null)
            ->has('uncategorized', 1)
        );
});
