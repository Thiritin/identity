<?php

use App\Jobs\PurgeOldNotificationsJob;
use App\Models\AppNotificationRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake([
        '*/admin/clients' => function () {
            return Http::response([
                'client_id' => 'test-client-id-' . uniqid(),
                'client_secret' => 'test-raw-secret-' . uniqid(),
            ], 200);
        },
        '*/admin/clients/*' => Http::response([
            'client_id' => 'test-client-id',
        ], 200),
    ]);
});

it('deletes notifications older than 90 days and preserves newer ones', function () {
    $old = AppNotificationRecord::factory()->create(['created_at' => now()->subDays(91)]);
    $edge = AppNotificationRecord::factory()->create(['created_at' => now()->subDays(90)->subMinute()]);
    $fresh = AppNotificationRecord::factory()->create(['created_at' => now()->subDays(10)]);

    (new PurgeOldNotificationsJob())->handle();

    expect(AppNotificationRecord::find($old->id))->toBeNull();
    expect(AppNotificationRecord::find($edge->id))->toBeNull();
    expect(AppNotificationRecord::find($fresh->id))->not->toBeNull();
});
