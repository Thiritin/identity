<?php

namespace App\Http\Controllers\Profile\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TelegramController extends Controller
{
    public function generateCode(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;

        $existingCode = Cache::get("telegram_link_user:{$userId}");

        if ($existingCode) {
            Cache::forget("telegram_link:{$existingCode}");
        }

        $code = strtoupper(Str::random(6));

        while (Cache::has("telegram_link:{$code}")) {
            $code = strtoupper(Str::random(6));
        }

        $ttl = 600; // 10 minutes

        Cache::put("telegram_link:{$code}", $userId, $ttl);
        Cache::put("telegram_link_user:{$userId}", $code, $ttl);

        $botUsername = config('nutgram.config.bot_name', 'EurofurenceBot');

        return response()->json([
            'code' => $code,
            'deep_link' => "https://t.me/{$botUsername}?start={$code}",
            'expires_in' => $ttl,
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'linked' => $user->telegram_id !== null,
            'telegram_username' => $user->telegram_username,
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->telegram_id = null;
        $user->telegram_username = null;
        $user->save();

        return response()->json(['success' => true]);
    }
}
