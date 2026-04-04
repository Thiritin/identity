<?php

use App\Enums\NotificationCategory;
use App\Models\App;
use App\Models\AppNotificationRecord;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake([
        '*/admin/clients' => Http::response([
            'client_id' => 'test-client-id-' . uniqid(),
            'client_secret' => 'test-raw-secret-' . uniqid(),
            'client_name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'scope' => 'openid',
            'token_endpoint_auth_method' => 'client_secret_post',
            'subject_type' => 'public',
            'post_logout_redirect_uris' => [],
        ]),
        '*/admin/clients/*' => Http::response([
            'client_id' => 'test-client-id',
            'client_name' => 'Test App',
            'redirect_uris' => ['https://example.com/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'scope' => 'openid',
            'token_endpoint_auth_method' => 'client_secret_post',
            'subject_type' => 'public',
            'post_logout_redirect_uris' => [],
        ]),
    ]);

    $this->user = User::factory()->create(['is_developer' => true]);
    $this->devApp = App::factory()->create([
        'user_id' => $this->user->id,
        'allow_notifications' => true,
    ]);
});

it('lists notification types for the authenticated owner', function () {
    NotificationType::factory()->count(3)->create(['app_id' => $this->devApp->id]);

    $this->actingAs($this->user)
        ->get("/developers/{$this->devApp->id}/notification-types")
        ->assertOk();
});

it('prevents access when app does not have allow_notifications enabled', function () {
    $this->devApp->update(['allow_notifications' => false]);

    $this->actingAs($this->user)
        ->get("/developers/{$this->devApp->id}/notification-types")
        ->assertForbidden();
});

it('prevents access by non-owners', function () {
    $other = User::factory()->create(['is_developer' => true]);

    $this->actingAs($other)
        ->get("/developers/{$this->devApp->id}/notification-types")
        ->assertForbidden();
});

it('creates a notification type with valid input', function () {
    $this->actingAs($this->user)
        ->post("/developers/{$this->devApp->id}/notification-types", [
            'key' => 'payment_reminder',
            'name' => 'Payment reminder',
            'description' => 'Reminds users to pay',
            'category' => 'operational',
            'default_channels' => ['email', 'database'],
        ])
        ->assertRedirect();

    expect(NotificationType::where('key', 'payment_reminder')->exists())->toBeTrue();
});

it('rejects transactional type without email in default channels', function () {
    $this->actingAs($this->user)
        ->post("/developers/{$this->devApp->id}/notification-types", [
            'key' => 'critical',
            'name' => 'Critical',
            'category' => 'transactional',
            'default_channels' => ['telegram'],
        ])
        ->assertSessionHasErrors('default_channels');
});

it('rejects invalid key format', function () {
    $this->actingAs($this->user)
        ->post("/developers/{$this->devApp->id}/notification-types", [
            'key' => 'Bad-Key',
            'name' => 'Bad',
            'category' => 'operational',
            'default_channels' => ['email'],
        ])
        ->assertSessionHasErrors('key');
});

it('rejects duplicate key within same app', function () {
    NotificationType::factory()->create(['app_id' => $this->devApp->id, 'key' => 'dupe']);

    $this->actingAs($this->user)
        ->post("/developers/{$this->devApp->id}/notification-types", [
            'key' => 'dupe',
            'name' => 'Dupe',
            'category' => 'operational',
            'default_channels' => ['email'],
        ])
        ->assertSessionHasErrors('key');
});

it('updates mutable fields', function () {
    $type = NotificationType::factory()->create(['app_id' => $this->devApp->id, 'key' => 'orig', 'name' => 'Orig']);

    $this->actingAs($this->user)
        ->put("/developers/{$this->devApp->id}/notification-types/{$type->id}", [
            'name' => 'Updated',
            'description' => 'New desc',
            'default_channels' => ['email', 'telegram'],
        ])
        ->assertRedirect();

    expect($type->fresh()->name)->toBe('Updated');
});

it('refuses to change key or category via update', function () {
    $type = NotificationType::factory()->create([
        'app_id' => $this->devApp->id,
        'key' => 'orig',
        'category' => NotificationCategory::Operational,
    ]);

    $this->actingAs($this->user)
        ->put("/developers/{$this->devApp->id}/notification-types/{$type->id}", [
            'key' => 'new_key',
            'category' => 'promotional',
            'name' => 'Same',
            'default_channels' => ['email'],
        ])
        ->assertRedirect();

    expect($type->fresh()->key)->toBe('orig');
    expect($type->fresh()->category)->toBe(NotificationCategory::Operational);
});

it('hard deletes a type that has no notifications', function () {
    $type = NotificationType::factory()->create(['app_id' => $this->devApp->id]);

    $this->actingAs($this->user)
        ->delete("/developers/{$this->devApp->id}/notification-types/{$type->id}")
        ->assertRedirect();

    expect(NotificationType::find($type->id))->toBeNull();
});

it('refuses hard delete when notifications reference the type', function () {
    $type = NotificationType::factory()->create(['app_id' => $this->devApp->id]);
    AppNotificationRecord::create([
        'app_id' => $this->devApp->id,
        'notification_type_id' => $type->id,
        'user_id' => $this->user->id,
        'subject' => 'x',
        'body' => 'y',
        'created_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->delete("/developers/{$this->devApp->id}/notification-types/{$type->id}")
        ->assertSessionHasErrors();

    expect(NotificationType::find($type->id))->not->toBeNull();
});

it('disables a type', function () {
    $type = NotificationType::factory()->create(['app_id' => $this->devApp->id]);

    $this->actingAs($this->user)
        ->post("/developers/{$this->devApp->id}/notification-types/{$type->id}/disable")
        ->assertRedirect();

    expect($type->fresh()->disabled)->toBeTrue();
});
