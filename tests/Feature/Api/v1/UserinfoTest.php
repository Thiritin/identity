<?php

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
        "data" => [
            "sub" => $user->hashid
        ]
    ]);
    $response->assertJsonMissing([
        "data" => [
            "name" => $user->name
        ]
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
        "data" => [
            "sub" => $user->hashid,
            "name" => $user->name
        ]
    ]);
});
