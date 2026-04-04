<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

it('generates a link code for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson(route('settings.telegram.generate-code'));

    $response->assertSuccessful()
        ->assertJsonStructure(['code', 'deep_link', 'expires_in']);

    $code = $response->json('code');
    expect($code)->toMatch('/^[A-Z0-9]{6}$/');
    expect(Cache::get("telegram_link:{$code}"))->toBe($user->id);
    expect(Cache::get("telegram_link_user:{$user->id}"))->toBe($code);
});

it('invalidates previous code when generating a new one', function () {
    $user = User::factory()->create();

    $first = $this->actingAs($user)
        ->postJson(route('settings.telegram.generate-code'))
        ->json('code');

    $second = $this->actingAs($user)
        ->postJson(route('settings.telegram.generate-code'))
        ->json('code');

    expect(Cache::get("telegram_link:{$first}"))->toBeNull();
    expect(Cache::get("telegram_link:{$second}"))->toBe($user->id);
});

it('returns linked telegram status', function () {
    $user = User::factory()->create([
        'telegram_id' => 123456789,
        'telegram_username' => 'testuser',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('settings.telegram.status'));

    $response->assertSuccessful()
        ->assertJson([
            'linked' => true,
            'telegram_username' => 'testuser',
        ]);
});

it('returns unlinked status when no telegram', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson(route('settings.telegram.status'));

    $response->assertSuccessful()
        ->assertJson(['linked' => false, 'telegram_username' => null]);
});

it('disconnects telegram account', function () {
    $user = User::factory()->create([
        'telegram_id' => 123456789,
        'telegram_username' => 'testuser',
    ]);

    $response = $this->actingAs($user)
        ->deleteJson(route('settings.telegram.disconnect'));

    $response->assertSuccessful();
    $user->refresh();
    expect($user->telegram_id)->toBeNull();
    expect($user->telegram_username)->toBeNull();
});

it('rate limits code generation', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user)
            ->postJson(route('settings.telegram.generate-code'))
            ->assertSuccessful();
    }

    $this->actingAs($user)
        ->postJson(route('settings.telegram.generate-code'))
        ->assertTooManyRequests();
});

it('requires authentication for all telegram endpoints', function () {
    $this->postJson(route('settings.telegram.generate-code'))->assertUnauthorized();
    $this->getJson(route('settings.telegram.status'))->assertUnauthorized();
    $this->deleteJson(route('settings.telegram.disconnect'))->assertUnauthorized();
});
