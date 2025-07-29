<?php

use App\Domains\Staff\Enums\GroupUserLevel;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

test('Userinfo Authenticated - Only Sub', function () {
    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['openid']
    );

    $response = get(route('api.v1.userinfo'));
    $response->assertStatus(200);
    $response->assertJson([
        'sub' => $user->hashid,
    ]);
    $response->assertJsonMissing([
        'name' => $user->name,
    ]);
});

test('Userinfo Authenticated - Profile Scope', function () {
    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['profile']
    );

    $response = get(route('api.v1.userinfo'));
    $response->assertStatus(200);
    $response->assertJson([
        'sub' => $user->hashid,
        'name' => $user->name,
    ]);
});

test('Userinfo Authenticated - Show groups that user is member', function () {
    $user = Sanctum::actingAs(
        User::factory()->create(),
        ['groups']
    );

    $group = Group::factory()->createQuietly();
    /**
     * @type User $user
     */
    $user->groups()->attach($group, ['level' => GroupUserLevel::Member]);

    $response = get(route('api.v1.userinfo'));
    $response->assertStatus(200);
    $response->assertJsonFragment([
        'groups' => [
            $group->hashid,
        ],
    ]);
});

// echo hello
