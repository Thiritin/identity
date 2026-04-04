<?php

use App\Models\App;
use App\Models\AppNotificationRecord;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake([
        '*admin/clients*' => Http::sequence()
            ->push(['client_id' => 'stub-a', 'client_secret' => 'secret-a'])
            ->push(['client_id' => 'stub-b', 'client_secret' => 'secret-b'])
            ->push(['client_id' => 'stub-c', 'client_secret' => 'secret-c'])
            ->whenEmpty(Http::response(['client_id' => 'stub-x', 'client_secret' => 'secret-x'])),
    ]);

    $this->me = User::factory()->create();
    $this->appModel = App::factory()->create(['allow_notifications' => true]);
    $this->type = NotificationType::factory()->create(['app_id' => $this->appModel->id]);
});

function makeRecord(\App\Models\User $user, \App\Models\App $app, \App\Models\NotificationType $type, array $overrides = []): \App\Models\AppNotificationRecord
{
    return AppNotificationRecord::create(array_merge([
        'app_id' => $app->id,
        'notification_type_id' => $type->id,
        'user_id' => $user->id,
        'subject' => 'Test',
        'body' => 'Body',
        'created_at' => now(),
    ], $overrides));
}

it('returns recent 5 with unread count', function () {
    for ($i = 0; $i < 6; $i++) {
        makeRecord($this->me, $this->appModel, $this->type);
    }

    $this->actingAs($this->me)
        ->getJson('/notifications/recent')
        ->assertOk()
        ->assertJsonCount(5, 'recent')
        ->assertJsonPath('unread_count', 6);
});

it('marks single notification as read', function () {
    $record = makeRecord($this->me, $this->appModel, $this->type);

    $this->actingAs($this->me)
        ->postJson("/notifications/{$record->id}/read")
        ->assertOk();

    expect($record->fresh()->read_at)->not->toBeNull();
});

it('refuses to mark another user\'s notification as read', function () {
    $other = User::factory()->create();
    $record = makeRecord($other, $this->appModel, $this->type);

    $this->actingAs($this->me)
        ->postJson("/notifications/{$record->id}/read")
        ->assertNotFound();
});

it('marks all as read for current user only', function () {
    $other = User::factory()->create();
    makeRecord($this->me, $this->appModel, $this->type);
    makeRecord($this->me, $this->appModel, $this->type);
    makeRecord($other, $this->appModel, $this->type);

    $this->actingAs($this->me)
        ->postJson('/notifications/read-all')
        ->assertOk();

    expect(AppNotificationRecord::where('user_id', $this->me->id)->whereNull('read_at')->count())->toBe(0);
    expect(AppNotificationRecord::where('user_id', $other->id)->whereNull('read_at')->count())->toBe(1);
});

it('clears all notifications for current user only', function () {
    $other = User::factory()->create();
    makeRecord($this->me, $this->appModel, $this->type);
    makeRecord($other, $this->appModel, $this->type);

    $this->actingAs($this->me)
        ->deleteJson('/notifications')
        ->assertOk();

    expect(AppNotificationRecord::where('user_id', $this->me->id)->count())->toBe(0);
    expect(AppNotificationRecord::where('user_id', $other->id)->count())->toBe(1);
});

it('deletes a single notification', function () {
    $record = makeRecord($this->me, $this->appModel, $this->type);

    $this->actingAs($this->me)
        ->deleteJson("/notifications/{$record->id}")
        ->assertOk();

    expect(AppNotificationRecord::find($record->id))->toBeNull();
});
