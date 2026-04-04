<?php

use App\Enums\NotificationCategory;
use App\Models\NotificationType;
use App\Models\User;
use App\Services\Notifications\NotificationPreferenceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    // NotificationType::factory() → App::factory() calls Hydra during creation.
    // Stub the admin/clients endpoints so the tests are hermetic.
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
});

function makeResolver(): NotificationPreferenceResolver
{
    return new NotificationPreferenceResolver();
}

it('returns default_channels when user has no preferences', function () {
    $user = User::factory()->create(['notification_preferences' => null]);
    $type = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email', 'telegram', 'database'],
    ]);

    $result = makeResolver()->resolve($user, $type);

    expect($result)->toEqualCanonicalizing(['email', 'telegram', 'database']);
});

it('applies full override map to drop a channel', function () {
    $type = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email', 'telegram', 'database'],
    ]);
    $user = User::factory()->create([
        'notification_preferences' => [
            'channels' => ['email' => true, 'telegram' => true, 'database' => true],
            'types' => [(string) $type->id => ['email' => false, 'telegram' => true, 'database' => true]],
        ],
    ]);

    $result = makeResolver()->resolve($user, $type);

    expect($result)->toEqualCanonicalizing(['telegram', 'database']);
});

it('falls back to default for channels missing from a partial override', function () {
    $type = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email', 'telegram', 'database'],
    ]);
    $user = User::factory()->create([
        'notification_preferences' => [
            'channels' => ['email' => true, 'telegram' => true, 'database' => true],
            'types' => [(string) $type->id => ['telegram' => false]],
        ],
    ]);

    $result = makeResolver()->resolve($user, $type);

    expect($result)->toEqualCanonicalizing(['email', 'database']);
});

it('ignores override channels not in current default_channels', function () {
    $type = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email'],
    ]);
    $user = User::factory()->create([
        'notification_preferences' => [
            'channels' => ['email' => true, 'telegram' => true, 'database' => true],
            'types' => [(string) $type->id => ['email' => true, 'telegram' => true]],
        ],
    ]);

    $result = makeResolver()->resolve($user, $type);

    expect($result)->toBe(['email']);
});

it('applies master switch to remove a channel', function () {
    $type = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email', 'telegram', 'database'],
    ]);
    $user = User::factory()->create([
        'notification_preferences' => [
            'channels' => ['email' => true, 'telegram' => false, 'database' => true],
            'types' => [],
        ],
    ]);

    $result = makeResolver()->resolve($user, $type);

    expect($result)->toEqualCanonicalizing(['email', 'database']);
});

it('restores full default_channels for transactional despite overrides and master switches', function () {
    $type = NotificationType::factory()->transactional()->create([
        'default_channels' => ['email', 'telegram'],
    ]);
    $user = User::factory()->create([
        'notification_preferences' => [
            'channels' => ['email' => false, 'telegram' => false, 'database' => false],
            'types' => [(string) $type->id => ['email' => false, 'telegram' => false]],
        ],
    ]);

    $result = makeResolver()->resolve($user, $type);

    expect($result)->toEqualCanonicalizing(['email', 'telegram']);
});

it('returns telegram in resolved list even when user has no telegram_id', function () {
    $type = NotificationType::factory()->create([
        'default_channels' => ['email', 'telegram'],
    ]);
    $user = User::factory()->create([
        'telegram_id' => null,
        'notification_preferences' => null,
    ]);

    $result = makeResolver()->resolve($user, $type);

    expect($result)->toContain('telegram');
});

it('returns empty array when all channels removed for non-transactional', function () {
    $type = NotificationType::factory()->create([
        'default_channels' => ['email', 'telegram'],
    ]);
    $user = User::factory()->create([
        'notification_preferences' => [
            'channels' => ['email' => false, 'telegram' => false, 'database' => false],
            'types' => [],
        ],
    ]);

    $result = makeResolver()->resolve($user, $type);

    expect($result)->toBe([]);
});

it('ignores stale override channels when default_channels shrinks', function () {
    $type = NotificationType::factory()->create([
        'default_channels' => ['email'],
    ]);
    $user = User::factory()->create([
        'notification_preferences' => [
            'channels' => ['email' => true, 'telegram' => true, 'database' => true],
            'types' => [(string) $type->id => ['email' => true, 'telegram' => false]],
        ],
    ]);

    $result = makeResolver()->resolve($user, $type);

    expect($result)->toBe(['email']);
});
