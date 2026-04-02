<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Activitylog\Models\Activity;

it('isSuspended returns false for active users', function () {
    $user = User::factory()->create();

    expect($user->isSuspended())->toBeFalse();
});

it('isSuspended returns true for suspended users', function () {
    $user = User::factory()->suspended()->create();

    expect($user->isSuspended())->toBeTrue();
});

it('suspend sets suspended_at and clears remember_token', function () {
    Http::fake(['*/admin/oauth2/auth/sessions/login*' => Http::response(null, 204)]);

    $user = User::factory()->create(['remember_token' => 'some-token']);

    $user->suspend();
    $user->refresh();

    expect($user->suspended_at)->not->toBeNull()
        ->and($user->remember_token)->toBeNull();
});

it('suspend deletes sanctum tokens', function () {
    Http::fake(['*/admin/oauth2/auth/sessions/login*' => Http::response(null, 204)]);

    $user = User::factory()->create();
    $user->createToken('test-token');

    expect($user->tokens)->toHaveCount(1);

    $user->suspend();

    expect($user->tokens()->count())->toBe(0);
});

it('suspend logs activity', function () {
    Http::fake(['*/admin/oauth2/auth/sessions/login*' => Http::response(null, 204)]);

    $user = User::factory()->create();
    $user->suspend();

    $activity = Activity::where('subject_id', $user->id)
        ->where('subject_type', User::class)
        ->where('description', 'user-suspended')
        ->first();

    expect($activity)->not->toBeNull();
});

it('unsuspend clears suspended_at', function () {
    $user = User::factory()->suspended()->create();

    $user->unsuspend();
    $user->refresh();

    expect($user->suspended_at)->toBeNull();
});

it('unsuspend logs activity', function () {
    $user = User::factory()->suspended()->create();
    $user->unsuspend();

    $activity = Activity::where('subject_id', $user->id)
        ->where('subject_type', User::class)
        ->where('description', 'user-unsuspended')
        ->first();

    expect($activity)->not->toBeNull();
});

it('canAccessPanel blocks suspended admins', function () {
    $user = User::factory()->admin()->suspended()->create();

    expect($user->canAccessPanel(null))->toBeFalse();
});

it('canAccessPanel allows active admins', function () {
    $user = User::factory()->admin()->create();

    expect($user->canAccessPanel(null))->toBeTrue();
});
