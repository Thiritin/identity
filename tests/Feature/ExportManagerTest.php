<?php

use App\Enums\GroupTypeEnum;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Group::factory()->create([
        'type' => GroupTypeEnum::Department,
        'name' => 'Engineering',
    ]);
});

test('non-hr non-admin user cannot access export page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('export.index'))
        ->assertForbidden();
});

test('hr user can access export page', function () {
    $user = User::factory()->hr()->create();

    $this->actingAs($user)
        ->get(route('export.index'))
        ->assertOk();
});

test('admin user can access export page', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('export.index'))
        ->assertOk();
});

test('export returns csv with selected fields', function () {
    $admin = User::factory()->admin()->create();
    $staffUser = User::factory()->create([
        'name' => 'teststaffer',
        'firstname' => 'Test',
        'lastname' => 'Staffer',
        'pronouns' => 'they/them',
    ]);

    $this->department->users()->attach($staffUser, [
        'level' => 'member',
        'title' => 'Designer',
        'credit_as' => 'T. Staff',
    ]);

    $response = $this->actingAs($admin)
        ->post(route('export.download'), [
            'fields' => ['username', 'department', 'title'],
        ]);

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();
    expect($csv)->toContain('username,department,title')
        ->toContain('teststaffer,' . $this->department->name . ',Designer');
});

test('export validates at least one field is required', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('export.download'), ['fields' => []])
        ->assertSessionHasErrors('fields');
});

test('export rejects invalid field names', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('export.download'), ['fields' => ['password']])
        ->assertSessionHasErrors('fields.0');
});

test('non-hr non-admin user cannot download export', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('export.download'), ['fields' => ['username']])
        ->assertForbidden();
});
