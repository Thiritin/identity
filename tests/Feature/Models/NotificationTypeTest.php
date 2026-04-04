<?php

use App\Enums\NotificationCategory;
use App\Models\App;
use App\Models\NotificationType;
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
});

it('casts category to enum and default_channels to array', function () {
    $app = App::factory()->create();
    $type = NotificationType::create([
        'app_id' => $app->id,
        'key' => 'welcome',
        'name' => 'Welcome',
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email', 'database'],
    ]);

    expect($type->category)->toBe(NotificationCategory::Operational);
    expect($type->default_channels)->toBe(['email', 'database']);
});

it('prevents mutation of key after creation', function () {
    $app = App::factory()->create();
    $type = NotificationType::create([
        'app_id' => $app->id,
        'key' => 'original',
        'name' => 'Test',
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email'],
    ]);

    expect(fn () => $type->update(['key' => 'changed']))
        ->toThrow(DomainException::class, 'key is immutable');
});

it('prevents mutation of category after creation', function () {
    $app = App::factory()->create();
    $type = NotificationType::create([
        'app_id' => $app->id,
        'key' => 'original',
        'name' => 'Test',
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email'],
    ]);

    expect(fn () => $type->update(['category' => NotificationCategory::Promotional]))
        ->toThrow(DomainException::class, 'category is immutable');
});

it('allows mutation of name, description, default_channels, disabled', function () {
    $app = App::factory()->create();
    $type = NotificationType::create([
        'app_id' => $app->id,
        'key' => 'original',
        'name' => 'Original',
        'category' => NotificationCategory::Operational,
        'default_channels' => ['email'],
    ]);

    $type->update([
        'name' => 'Updated',
        'description' => 'Desc',
        'default_channels' => ['email', 'telegram'],
        'disabled' => true,
    ]);

    expect($type->fresh()->name)->toBe('Updated');
    expect($type->fresh()->default_channels)->toBe(['email', 'telegram']);
});
