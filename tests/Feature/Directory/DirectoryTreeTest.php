<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use App\Services\DirectoryTreeBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->root = Group::where('type', GroupTypeEnum::Root)->firstOrFail();
    $this->divisionA = Group::factory()->division()->create([
        'name' => 'Alpha Division',
        'parent_id' => $this->root->id,
    ]);
    $this->divisionB = Group::factory()->division()->create([
        'name' => 'Beta Division',
        'parent_id' => $this->root->id,
    ]);
    $this->department = Group::factory()->department()->create([
        'name' => 'Art Department',
        'parent_id' => $this->divisionA->id,
    ]);
    $this->team = Group::factory()->team()->create([
        'name' => 'Badge Team',
        'parent_id' => $this->department->id,
    ]);
});

test('tree builder skips root and returns divisions at top level', function () {
    $tree = app(DirectoryTreeBuilder::class)->build();
    expect($tree)->toHaveCount(2);
    expect($tree[0]['name'])->toBe('Alpha Division');
    expect($tree[0]['type'])->toBe('division');
    expect($tree[1]['name'])->toBe('Beta Division');
});

test('tree is sorted alphabetically at each level', function () {
    $tree = app(DirectoryTreeBuilder::class)->build();
    $divisionNames = collect($tree)->pluck('name')->all();
    expect($divisionNames)->toBe(['Alpha Division', 'Beta Division']);
});

test('tree includes member counts', function () {
    $user = User::factory()->create();
    $this->department->users()->attach($user, ['level' => GroupUserLevel::Member]);
    $tree = app(DirectoryTreeBuilder::class)->build();
    $dept = $tree[0]['children'][0];
    expect($dept['name'])->toBe('Art Department');
    expect($dept['member_count'])->toBe(1);
});

test('tree excludes automated and default groups', function () {
    Group::factory()->create(['type' => GroupTypeEnum::Automated, 'name' => 'Staff', 'system_name' => 'staff']);
    Group::factory()->create(['type' => GroupTypeEnum::Default, 'name' => 'Random']);
    $tree = app(DirectoryTreeBuilder::class)->build();
    $allNames = collect($tree)->pluck('name')->all();
    expect($allNames)->not->toContain('Staff');
    expect($allNames)->not->toContain('Random');
});

test('tree nests departments under divisions and teams under departments', function () {
    $tree = app(DirectoryTreeBuilder::class)->build();
    $division = $tree[0];
    expect($division['children'])->toHaveCount(1);
    expect($division['children'][0]['name'])->toBe('Art Department');
    expect($division['children'][0]['children'])->toHaveCount(1);
    expect($division['children'][0]['children'][0]['name'])->toBe('Badge Team');
});

test('tree marks user groups with is_mine', function () {
    $user = User::factory()->create();
    $this->department->users()->attach($user, ['level' => GroupUserLevel::Member]);

    $tree = app(DirectoryTreeBuilder::class)->build([$this->department->id]);
    $dept = $tree[0]['children'][0];
    expect($dept['is_mine'])->toBeTrue();
    expect($tree[0]['is_mine'])->toBeFalse();
});
