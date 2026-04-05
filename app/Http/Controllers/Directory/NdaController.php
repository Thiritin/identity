<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EdnaService;
use Illuminate\Http\JsonResponse;

class NdaController extends Controller
{
    public function __construct(private EdnaService $ednaService) {}

    public function check(User $user): JsonResponse
    {
        $this->authorizeDirector();

        $result = $this->ednaService->check($user->name, $user->email);

        if ($result->signed) {
            $user->update(['nda_checked_at' => now()]);
        }

        return response()->json([
            'signed' => $result->signed,
            'raw_status' => $result->rawStatus,
            'nda_checked_at' => $user->fresh()->nda_checked_at?->toIso8601String(),
        ]);
    }

    public function send(User $user): JsonResponse
    {
        $this->authorizeDirector();

        $locale = data_get($user->preferences, 'locale', 'en');
        $language = in_array($locale, ['en', 'de']) ? $locale : 'en';

        $this->ednaService->send($user->name, $user->email, $language);

        return response()->json(['sent' => true]);
    }

    private function authorizeDirector(): void
    {
        if (! request()->user()->hasStaffLevel([GroupUserLevel::Director, GroupUserLevel::DivisionDirector])) {
            abort(403);
        }
    }
}
