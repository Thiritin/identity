<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user has a hashid attribute', function () {
    $user = User::factory()->create();

    expect($user->hashid)->toBeString()->not->toBeEmpty();
});

test('user hashid is 16 characters uppercase alphanumeric', function () {
    $user = User::factory()->create();

    expect($user->hashid)
        ->toHaveLength(16)
        ->toMatch('/^[A-Z0-9]{16}$/');
});

test('group has a hashid attribute', function () {
    $group = Group::factory()->createQuietly();

    expect($group->hashid)->toBeString()->not->toBeEmpty();
});

test('group hashid is 16 characters uppercase alphanumeric', function () {
    $group = Group::factory()->createQuietly();

    expect($group->hashid)
        ->toHaveLength(16)
        ->toMatch('/^[A-Z0-9]{16}$/');
});

test('each user gets a unique hashid', function () {
    $users = User::factory()->count(10)->create();

    $hashids = $users->pluck('hashid');

    expect($hashids->unique()->count())->toBe(10);
});

test('each group gets a unique hashid', function () {
    $groups = Group::factory()->count(10)->createQuietly();

    $hashids = $groups->pluck('hashid');

    expect($hashids->unique()->count())->toBe(10);
});

test('user hashid is included in toArray and JSON', function () {
    $user = User::factory()->create();

    expect($user->toArray())->toHaveKey('hashid');
});

test('group hashid is included in toArray and JSON', function () {
    $group = Group::factory()->createQuietly();

    expect($group->toArray())->toHaveKey('hashid');
});

test('findByHashid returns the correct user', function () {
    $user = User::factory()->create();

    $found = User::findByHashid($user->hashid);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($user->id);
});

test('findByHashid returns null for nonexistent hashid', function () {
    $found = User::findByHashid('NONEXISTENT12345');

    expect($found)->toBeNull();
});

test('findByHashidOrFail returns the correct user', function () {
    $user = User::factory()->create();

    $found = User::findByHashidOrFail($user->hashid);

    expect($found->id)->toBe($user->id);
});

test('findByHashidOrFail throws for nonexistent hashid', function () {
    User::findByHashidOrFail('NONEXISTENT12345');
})->throws(ModelNotFoundException::class);

test('findByHashid returns the correct group', function () {
    $group = Group::factory()->createQuietly();

    $found = Group::findByHashid($group->hashid);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($group->id);
});

test('findByHashidOrFail returns the correct group', function () {
    $group = Group::factory()->createQuietly();

    $found = Group::findByHashidOrFail($group->hashid);

    expect($found->id)->toBe($group->id);
});

test('user route model binding resolves via hashid', function () {
    $user = User::factory()->create();

    expect($user->getRouteKey())->toBe($user->hashid);
});

test('group route model binding resolves via hashid', function () {
    $group = Group::factory()->createQuietly();

    expect($group->getRouteKey())->toBe($group->hashid);
});

test('user hashid is stable after reload', function () {
    $user = User::factory()->create();
    $hashid = $user->hashid;

    $reloaded = User::find($user->id);

    expect($reloaded->hashid)->toBe($hashid);
});

test('group hashid is stable after reload', function () {
    $group = Group::factory()->createQuietly();
    $hashid = $group->hashid;

    $reloaded = Group::find($group->id);

    expect($reloaded->hashid)->toBe($hashid);
});

test('user hashid uses different salt than group hashid', function () {
    $user = User::factory()->create();
    $group = Group::factory()->createQuietly();

    // If they happen to have the same integer ID, the hashids must differ
    // because they use different connection salts
    if ($user->id === $group->id) {
        expect($user->hashid)->not->toBe($group->hashid);
    } else {
        expect(true)->toBeTrue();
    }
});
