<?php

use App\Enums\NotificationCategory;
use App\Jobs\SendAppNotificationJob;
use App\Models\AppNotificationRecord;
use App\Models\NotificationType;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Notifications\NotificationPreferenceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

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

it('sends notification via resolved channels', function () {
    Notification::fake();
    $user = User::factory()->create();
    $type = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email', 'database'],
    ]);

    (new SendAppNotificationJob($type->id, $user->id, [
        'subject' => 'Hi',
        'body' => 'Body',
    ]))->handle(app(NotificationPreferenceResolver::class));

    Notification::assertSentTo($user, AppNotification::class, function ($n) {
        return in_array('mail', $n->via(null), true)
            && in_array(\App\Notifications\Channels\AppDatabaseChannel::class, $n->via(null), true);
    });
});

it('silently skips when type has been deleted', function () {
    Notification::fake();
    $user = User::factory()->create();
    $type = NotificationType::factory()->create();
    $typeId = $type->id;
    $type->delete();

    (new SendAppNotificationJob($typeId, $user->id, [
        'subject' => 'Hi',
        'body' => 'Body',
    ]))->handle(app(NotificationPreferenceResolver::class));

    Notification::assertNothingSent();
});

it('silently skips when type has been disabled', function () {
    Notification::fake();
    $user = User::factory()->create();
    $type = NotificationType::factory()->create(['disabled' => true]);

    (new SendAppNotificationJob($type->id, $user->id, [
        'subject' => 'Hi',
        'body' => 'Body',
    ]))->handle(app(NotificationPreferenceResolver::class));

    Notification::assertNothingSent();
});

it('silently skips when user has been deleted', function () {
    Notification::fake();
    $user = User::factory()->create();
    $userId = $user->id;
    $type = NotificationType::factory()->create();
    $user->delete();

    (new SendAppNotificationJob($type->id, $userId, [
        'subject' => 'Hi',
        'body' => 'Body',
    ]))->handle(app(NotificationPreferenceResolver::class));

    Notification::assertNothingSent();
});

it('silently skips when resolved channel list is empty', function () {
    Notification::fake();
    $type = NotificationType::factory()->create([
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email'],
    ]);
    $user = User::factory()->create([
        'notification_preferences' => [
            'channels' => ['email' => false, 'telegram' => false, 'database' => false],
            'types' => [],
        ],
    ]);

    (new SendAppNotificationJob($type->id, $user->id, [
        'subject' => 'Hi',
        'body' => 'Body',
    ]))->handle(app(NotificationPreferenceResolver::class));

    Notification::assertNothingSent();
});

it('still delivers transactional via email when user disabled master switch', function () {
    Notification::fake();
    $type = NotificationType::factory()->transactional()->create([
        'default_channels' => ['email'],
    ]);
    $user = User::factory()->create([
        'notification_preferences' => [
            'channels' => ['email' => false, 'telegram' => false, 'database' => false],
            'types' => [],
        ],
    ]);

    (new SendAppNotificationJob($type->id, $user->id, [
        'subject' => 'Important',
        'body' => 'Body',
    ]))->handle(app(NotificationPreferenceResolver::class));

    Notification::assertSentTo($user, AppNotification::class);
});

it('isolates channel failures: database write still happens when telegram throws', function () {
    // Does NOT use Notification::fake() — we need real channel classes to run.
    Mail::fake();

    $telegramMock = Mockery::mock(\SergiX44\Nutgram\Nutgram::class);
    $telegramMock->shouldReceive('sendMessage')->andThrow(new RuntimeException('telegram down'));
    app()->instance(\SergiX44\Nutgram\Nutgram::class, $telegramMock);

    $type = NotificationType::factory()->create([
        'default_channels' => ['email', 'telegram', 'database'],
    ]);
    $user = User::factory()->create(['telegram_id' => 123456]);

    (new SendAppNotificationJob($type->id, $user->id, [
        'subject' => 'Hi',
        'body' => 'Body',
    ]))->handle(app(NotificationPreferenceResolver::class));

    expect(AppNotificationRecord::where('user_id', $user->id)->count())->toBe(1);
});

it('skips telegram silently when user has no telegram_id and still delivers other channels', function () {
    Mail::fake();
    $type = NotificationType::factory()->create([
        'default_channels' => ['email', 'telegram', 'database'],
    ]);
    $user = User::factory()->create(['telegram_id' => null]);

    (new SendAppNotificationJob($type->id, $user->id, [
        'subject' => 'Hi',
        'body' => 'Body',
    ]))->handle(app(NotificationPreferenceResolver::class));

    expect(AppNotificationRecord::where('user_id', $user->id)->count())->toBe(1);
});
