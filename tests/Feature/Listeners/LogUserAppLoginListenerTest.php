<?php

use App\Events\AppLoginEvent;
use App\Models\App;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('logs activity when user logs into an app', function () {
    $user = User::factory()->create();
    $app = App::withoutEvents(fn () => App::create(['client_id' => 'test-client-id', 'user_id' => $user->id]));

    event(new AppLoginEvent($app->client_id, $user->hashid));

    $activity = Activity::query()->where('description', 'login-app')->first();

    expect($activity)
        ->not->toBeNull()
        ->subject_id->toBe($app->id)
        ->causer_id->toBe($user->id);
});

it('does not crash when app is not found', function () {
    $user = User::factory()->create();

    event(new AppLoginEvent('nonexistent-client-id', $user->hashid));

    $this->assertDatabaseMissing('activity_log', [
        'causer_id' => $user->id,
        'description' => 'login-app',
    ]);
});
