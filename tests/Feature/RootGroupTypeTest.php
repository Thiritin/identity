<?php

use App\Enums\GroupTypeEnum;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('root group type exists in enum', function () {
    expect(GroupTypeEnum::Root->value)->toBe('root');
});

test('migration creates root group with correct attributes', function () {
    $root = Group::where('type', GroupTypeEnum::Root)->first();

    expect($root)->not->toBeNull();
    expect($root->system_name)->toBe('board');
    expect($root->name)->toBe('Board of Directors');
});

test('factory can create division, department, and team groups', function () {
    $division = Group::factory()->division()->create();
    $department = Group::factory()->department()->create();
    $team = Group::factory()->team()->create();

    expect($division->type)->toBe(GroupTypeEnum::Division);
    expect($department->type)->toBe(GroupTypeEnum::Department);
    expect($team->type)->toBe(GroupTypeEnum::Team);
});

test('only one root group may exist', function () {
    // The migration already created a root group, so creating another should fail
    Group::factory()->root()->create();
})->throws(RuntimeException::class, 'Only one root group may exist.');

test('divisions are reparented under root group', function () {
    $root = Group::where('type', GroupTypeEnum::Root)->first();

    $divisionsUnderRoot = Group::where('type', GroupTypeEnum::Division)
        ->where('parent_id', $root->id)
        ->count();

    $orphanDivisions = Group::where('type', GroupTypeEnum::Division)
        ->whereNull('parent_id')
        ->count();

    expect($orphanDivisions)->toBe(0);
});

test('root group is included in staff viewable types for policy', function () {
    $staffGroup = Group::factory()->create(['system_name' => 'staff']);
    $rootGroup = Group::where('type', GroupTypeEnum::Root)->first();

    $staffUser = User::factory()->create();
    $staffGroup->users()->attach($staffUser->id, ['level' => 'member']);

    $response = $this->actingAs($staffUser)->get(route('api.v1.groups.show', $rootGroup));

    $response->assertSuccessful();
});
