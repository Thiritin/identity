<?php

use App\Enums\NotificationCategory;
use App\Models\App;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake([
        '*/admin/clients' => function () {
            return Http::response([
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
            ]);
        },
        '*/admin/clients/*' => function () {
            return Http::response([
                'client_id' => 'test-client-id-' . uniqid(),
                'client_name' => 'Test App',
                'redirect_uris' => ['https://example.com/callback'],
                'grant_types' => ['authorization_code', 'refresh_token'],
                'response_types' => ['code'],
                'scope' => 'openid',
                'token_endpoint_auth_method' => 'client_secret_post',
                'subject_type' => 'public',
                'post_logout_redirect_uris' => [],
            ]);
        },
    ]);
});

it('returns preferences page with enabled types grouped by app and category', function () {
    $user = User::factory()->create();
    $appModel = App::factory()->create(['allow_notifications' => true]);
    NotificationType::factory()->create(['app_id' => $appModel->id, 'disabled' => false]);
    NotificationType::factory()->create(['app_id' => $appModel->id, 'disabled' => true]);

    $this->actingAs($user)
        ->get('/settings/notifications')
        ->assertOk();
});

it('saves valid preferences', function () {
    $user = User::factory()->create();
    $type = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email', 'telegram', 'database'],
    ]);

    $this->actingAs($user)
        ->post('/settings/notifications', [
            'channels' => ['email' => true, 'telegram' => false, 'database' => true],
            'types' => [
                (string) $type->id => ['email' => true, 'telegram' => false, 'database' => true],
            ],
        ])
        ->assertRedirect();

    $prefs = $user->fresh()->notification_preferences;
    expect($prefs['channels']['telegram'])->toBeFalse();
    expect($prefs['types'][(string) $type->id]['telegram'])->toBeFalse();
});

it('rejects transactional override attempt', function () {
    $user = User::factory()->create();
    $type = NotificationType::factory()->transactional()->create();

    $this->actingAs($user)
        ->post('/settings/notifications', [
            'channels' => ['email' => true, 'telegram' => true, 'database' => true],
            'types' => [
                (string) $type->id => ['email' => false],
            ],
        ])
        ->assertSessionHasErrors();
});

it('drops stale channel keys from overrides on save', function () {
    $user = User::factory()->create();
    $type = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email'],
    ]);

    $this->actingAs($user)
        ->post('/settings/notifications', [
            'channels' => ['email' => true, 'telegram' => true, 'database' => true],
            'types' => [
                (string) $type->id => ['email' => true, 'telegram' => false, 'database' => false],
            ],
        ]);

    $prefs = $user->fresh()->notification_preferences;
    expect($prefs['types'][(string) $type->id])->toBe(['email' => true]);
});

it('preserves overrides for disabled types not in the submitted payload', function () {
    $user = User::factory()->create();
    $visibleType = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email', 'telegram'],
    ]);
    $disabledType = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email'],
        'disabled' => true,
    ]);

    $user->notification_preferences = [
        'channels' => ['email' => true, 'telegram' => true, 'database' => true],
        'types' => [
            (string) $disabledType->id => ['email' => false],
        ],
    ];
    $user->save();

    $this->actingAs($user)
        ->post('/settings/notifications', [
            'channels' => ['email' => true, 'telegram' => true, 'database' => true],
            'types' => [
                (string) $visibleType->id => ['email' => true, 'telegram' => false],
            ],
        ])
        ->assertRedirect();

    $prefs = $user->fresh()->notification_preferences;
    expect($prefs['types'][(string) $disabledType->id])->toBe(['email' => false]);
    expect($prefs['types'][(string) $visibleType->id])->toBe(['email' => true, 'telegram' => false]);
});
