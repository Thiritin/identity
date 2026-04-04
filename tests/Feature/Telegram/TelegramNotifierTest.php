<?php

use App\Models\User;
use App\Services\TelegramNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use SergiX44\Nutgram\Nutgram;

uses(RefreshDatabase::class);

it('sends login notification to linked telegram user', function () {
    $user = User::factory()->create([
        'telegram_id' => 123456,
        'telegram_username' => 'testuser',
    ]);

    $bot = app(Nutgram::class);
    $bot->willReceivePartial([]);

    $notifier = new TelegramNotifier($bot);
    $notifier->notifyLogin($user, '192.168.1.1');

    $bot->assertReply('sendMessage', [
        'chat_id' => 123456,
    ]);
});

it('sends password changed notification to linked telegram user', function () {
    $user = User::factory()->create([
        'telegram_id' => 123456,
        'telegram_username' => 'testuser',
    ]);

    $bot = app(Nutgram::class);
    $bot->willReceivePartial([]);

    $notifier = new TelegramNotifier($bot);
    $notifier->notifyPasswordChanged($user);

    $bot->assertReply('sendMessage', [
        'chat_id' => 123456,
    ]);
});

it('sends email changed notification to linked telegram user', function () {
    $user = User::factory()->create([
        'telegram_id' => 123456,
        'telegram_username' => 'testuser',
    ]);

    $bot = app(Nutgram::class);
    $bot->willReceivePartial([]);

    $notifier = new TelegramNotifier($bot);
    $notifier->notifyEmailChanged($user, 'old@test.com', 'new@test.com');

    $bot->assertReply('sendMessage', [
        'chat_id' => 123456,
    ]);
});

it('does not send notification when user has no telegram id', function () {
    $user = User::factory()->create([
        'telegram_id' => null,
        'telegram_username' => null,
    ]);

    $bot = app(Nutgram::class);
    $notifier = new TelegramNotifier($bot);
    $notifier->notifyLogin($user);

    $bot->assertNoReply();
});
