<?php

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Models\Convention;
use App\Models\ConventionAttendee;
use App\Models\Group;
use App\Models\User;
use App\Services\Auth\ApiGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\Concerns\ValidatesOpenApiV2;

uses(RefreshDatabase::class, ValidatesOpenApiV2::class);

function actingAsAttendanceApiUser(User $user, string $clientId, array $scopes = []): void
{
    $guard = Mockery::mock(ApiGuard::class);
    $guard->shouldReceive('user')->andReturn($user);
    $guard->shouldReceive('check')->andReturn(true);
    $guard->shouldReceive('guest')->andReturn(false);
    $guard->shouldReceive('id')->andReturn($user->id);
    $guard->shouldReceive('hasUser')->andReturn(true);
    $guard->shouldReceive('getClientId')->andReturn($clientId);
    $guard->shouldReceive('getScopes')->andReturn($scopes);
    $guard->shouldReceive('setRequest')->andReturnSelf();

    Auth::extend('hydra', fn () => $guard);
    Auth::forgetGuards();
}

// ── Index ────────────────────────────────────────────────────────────

it('lists own attendances with Attendances.Read', function () {
    $user = User::factory()->create();
    $convention = Convention::factory()->create(['year' => 2026]);
    ConventionAttendee::create([
        'user_id' => $user->id,
        'convention_id' => $convention->id,
        'is_attended' => true,
        'is_staff' => false,
    ]);

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.Read']);

    $response = $this->getJson('/api/v2/users/me/attendances')
        ->assertOk();

    expect($response->json())->toHaveCount(1);
    expect($response->json(0))->toHaveKeys(['id', 'convention_id', 'is_attended', 'is_staff']);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances');
});

it('returns empty array when no attendances', function () {
    $user = User::factory()->create();

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.Read']);

    $response = $this->getJson('/api/v2/users/me/attendances')
        ->assertOk();

    expect($response->json())->toBeArray()->toBeEmpty();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances');
});

it('includes convention when requested', function () {
    $user = User::factory()->create();
    $convention = Convention::factory()->create(['year' => 2026]);
    ConventionAttendee::create([
        'user_id' => $user->id,
        'convention_id' => $convention->id,
        'is_attended' => true,
        'is_staff' => false,
    ]);

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.Read']);

    $response = $this->getJson('/api/v2/users/me/attendances?include=convention')
        ->assertOk();

    expect($response->json(0))->toHaveKey('convention');
    expect($response->json('0.convention.id'))->toBe($convention->id);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances');
});

it('returns 403 without Attendances.Read scope', function () {
    $user = User::factory()->create();

    actingAsAttendanceApiUser($user, 'app-one', ['User.Read']);

    $response = $this->getJson('/api/v2/users/me/attendances')
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances');
});

// ── Store ────────────────────────────────────────────────────────────

it('creates attendance with Attendances.ReadWrite and returns 201', function () {
    $user = User::factory()->create();
    $convention = Convention::factory()->create(['year' => 2026]);

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.ReadWrite']);

    $response = $this->postJson('/api/v2/users/me/attendances', [
        'convention_id' => $convention->id,
    ])->assertCreated();

    expect($response->json('is_attended'))->toBeTrue();
    expect($response->json('is_staff'))->toBeFalse();
    expect($response->json('convention_id'))->toBe($convention->id);

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances', 'post');
});

it('returns 422 for duplicate convention_id', function () {
    $user = User::factory()->create();
    $convention = Convention::factory()->create(['year' => 2026]);
    ConventionAttendee::create([
        'user_id' => $user->id,
        'convention_id' => $convention->id,
        'is_attended' => true,
        'is_staff' => false,
    ]);

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.ReadWrite']);

    $response = $this->postJson('/api/v2/users/me/attendances', [
        'convention_id' => $convention->id,
    ])->assertUnprocessable();
});

it('returns 422 for non-existent convention_id', function () {
    $user = User::factory()->create();

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.ReadWrite']);

    $response = $this->postJson('/api/v2/users/me/attendances', [
        'convention_id' => 99999,
    ])->assertUnprocessable();
});

it('returns 403 without Attendances.ReadWrite scope on store', function () {
    $user = User::factory()->create();

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.Read']);

    $response = $this->postJson('/api/v2/users/me/attendances', [
        'convention_id' => 1,
    ])->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances', 'post');
});

// ── Update ───────────────────────────────────────────────────────────

it('updates is_attended with Attendances.ReadWrite', function () {
    $user = User::factory()->create();
    $convention = Convention::factory()->create(['year' => 2026]);
    $attendance = ConventionAttendee::create([
        'user_id' => $user->id,
        'convention_id' => $convention->id,
        'is_attended' => true,
        'is_staff' => false,
    ]);

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.ReadWrite']);

    $response = $this->putJson("/api/v2/users/me/attendances/{$attendance->id}", [
        'is_attended' => false,
    ])->assertOk();

    expect($response->json('is_attended'))->toBeFalse();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances/{attendance}', 'put');
});

it('cannot update own is_staff', function () {
    $user = User::factory()->create();
    $convention = Convention::factory()->create(['year' => 2026]);
    $attendance = ConventionAttendee::create([
        'user_id' => $user->id,
        'convention_id' => $convention->id,
        'is_attended' => true,
        'is_staff' => false,
    ]);

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.ReadWrite']);

    $response = $this->putJson("/api/v2/users/me/attendances/{$attendance->id}", [
        'is_staff' => true,
    ])->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances/{attendance}', 'put');
});

it('director can update is_staff on other user', function () {
    $viewer = User::factory()->create();
    $target = User::factory()->create();
    $convention = Convention::factory()->create(['year' => 2026]);
    $attendance = ConventionAttendee::create([
        'user_id' => $target->id,
        'convention_id' => $convention->id,
        'is_attended' => true,
        'is_staff' => false,
    ]);

    $group = Group::factory()->create(['type' => GroupTypeEnum::Department]);
    $group->users()->attach($target->id, ['level' => GroupUserLevel::Member]);
    $group->users()->attach($viewer->id, ['level' => GroupUserLevel::Director]);

    actingAsAttendanceApiUser($viewer, 'app-one', ['Attendances.ReadWrite', 'Attendances.ReadWrite.All']);

    $response = $this->putJson('/api/v2/users/' . $target->hashid . '/attendances/' . $attendance->id, [
        'is_staff' => true,
    ])->assertOk();

    expect($response->json('is_staff'))->toBeTrue();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances/{attendance}', 'put');
});

it('returns 404 for non-existent attendance on update', function () {
    $user = User::factory()->create();

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.ReadWrite']);

    $response = $this->putJson('/api/v2/users/me/attendances/99999', [
        'is_attended' => false,
    ])->assertNotFound();
});

// ── Destroy ──────────────────────────────────────────────────────────

it('deletes attendance and returns 204', function () {
    $user = User::factory()->create();
    $convention = Convention::factory()->create(['year' => 2026]);
    $attendance = ConventionAttendee::create([
        'user_id' => $user->id,
        'convention_id' => $convention->id,
        'is_attended' => true,
        'is_staff' => false,
    ]);

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.ReadWrite']);

    $response = $this->deleteJson("/api/v2/users/me/attendances/{$attendance->id}")
        ->assertNoContent();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances/{attendance}', 'delete');
});

it('returns 409 when deleting staff attendance', function () {
    $user = User::factory()->create();
    $convention = Convention::factory()->create(['year' => 2026]);
    $attendance = ConventionAttendee::create([
        'user_id' => $user->id,
        'convention_id' => $convention->id,
        'is_attended' => true,
        'is_staff' => true,
    ]);

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.ReadWrite']);

    $response = $this->deleteJson("/api/v2/users/me/attendances/{$attendance->id}")
        ->assertConflict();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances/{attendance}', 'delete');
});

it('returns 404 for non-existent attendance on destroy', function () {
    $user = User::factory()->create();

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.ReadWrite']);

    $response = $this->deleteJson('/api/v2/users/me/attendances/99999')
        ->assertNotFound();
});

it('returns 403 without scope on destroy', function () {
    $user = User::factory()->create();

    actingAsAttendanceApiUser($user, 'app-one', ['Attendances.Read']);

    $response = $this->deleteJson('/api/v2/users/me/attendances/1')
        ->assertForbidden();

    $this->assertMatchesOpenApiV2($response, '/users/{user}/attendances/{attendance}', 'delete');
});
