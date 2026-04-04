<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\User\User as TelegramUser;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->bot = app(Nutgram::class);
});

it('responds to /start without code with welcome message', function () {
    $this->bot
        ->hearText('/start')
        ->willReceivePartial([])
        ->reply()
        ->assertReplyText("Welcome to the Eurofurence bot!\n\nTo link your Telegram account, visit your profile settings and click 'Connect Telegram'.");
});

it('links account with valid code and username', function () {
    $user = User::factory()->create();
    $code = 'ABC123';
    Cache::put("telegram_link:{$code}", $user->id, 600);
    Cache::put("telegram_link_user:{$user->id}", $code, 600);

    $this->bot
        ->setCommonUser(TelegramUser::make(id: 999888, is_bot: false, first_name: 'Test', username: 'tguser'))
        ->hearText("/start {$code}")
        ->willReceivePartial([])
        ->reply()
        ->assertReplyText("You have been successfully linked with {$user->name} on Eurofurence Identity!");

    $user->refresh();
    expect($user->telegram_id)->toBe(999888);
    expect($user->telegram_username)->toBe('tguser');
    expect(Cache::get("telegram_link:{$code}"))->toBeNull();
});

it('rejects code when user has no telegram username', function () {
    $user = User::factory()->create();
    $code = 'XYZ789';
    Cache::put("telegram_link:{$code}", $user->id, 600);

    $this->bot
        ->setCommonUser(TelegramUser::make(id: 999888, is_bot: false, first_name: 'Test', username: null))
        ->hearText("/start {$code}")
        ->willReceivePartial([])
        ->reply()
        ->assertReplyText("You need to set a Telegram username before linking.\n\nGo to Telegram Settings > Username to set one, then try again.");

    $user->refresh();
    expect($user->telegram_id)->toBeNull();
});

it('rejects expired or invalid code', function () {
    $this->bot
        ->setCommonUser(TelegramUser::make(id: 999888, is_bot: false, first_name: 'Test', username: 'tguser'))
        ->hearText('/start BADCOD')
        ->willReceivePartial([])
        ->reply()
        ->assertReplyText('This code is invalid or has expired. Please generate a new code from your profile settings.');
});

it('rejects when telegram id already linked to another user', function () {
    User::factory()->create([
        'telegram_id' => 999888,
        'telegram_username' => 'tguser',
    ]);
    $newUser = User::factory()->create();
    $code = 'DUP456';
    Cache::put("telegram_link:{$code}", $newUser->id, 600);

    $this->bot
        ->setCommonUser(TelegramUser::make(id: 999888, is_bot: false, first_name: 'Test', username: 'tguser'))
        ->hearText("/start {$code}")
        ->willReceivePartial([])
        ->reply()
        ->assertReplyText('This Telegram account is already linked to another user. Please unlink it first using /unlink.');
});

it('handles plain text code input via fallback', function () {
    $user = User::factory()->create();
    $code = 'TXT321';
    Cache::put("telegram_link:{$code}", $user->id, 600);
    Cache::put("telegram_link_user:{$user->id}", $code, 600);

    $this->bot
        ->setCommonUser(TelegramUser::make(id: 111222, is_bot: false, first_name: 'Test', username: 'plainuser'))
        ->hearText($code)
        ->willReceivePartial([])
        ->reply()
        ->assertReplyText("You have been successfully linked with {$user->name} on Eurofurence Identity!");
});

it('responds with fallback for unknown text', function () {
    $this->bot
        ->hearText('hello world')
        ->willReceivePartial([])
        ->reply()
        ->assertReplyText("I don't understand that. To link your account, visit your profile settings and click 'Connect Telegram'.");
});

it('unlinks when user is linked', function () {
    $user = User::factory()->create([
        'telegram_id' => 555666,
        'telegram_username' => 'linkeduser',
    ]);

    $this->bot
        ->setCommonUser(TelegramUser::make(id: 555666, is_bot: false, first_name: 'Test', username: 'linkeduser'))
        ->hearText('/unlink')
        ->willReceivePartial([])
        ->reply()
        ->assertReplyText('Your Telegram account has been unlinked.');

    $user->refresh();
    expect($user->telegram_id)->toBeNull();
});

it('responds when /unlink but not linked', function () {
    $this->bot
        ->setCommonUser(TelegramUser::make(id: 999999, is_bot: false, first_name: 'Test', username: 'nobody'))
        ->hearText('/unlink')
        ->willReceivePartial([])
        ->reply()
        ->assertReplyText('Your Telegram account is not linked to any account.');
});
