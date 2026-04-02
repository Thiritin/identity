<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePreferencesRequest;
use Illuminate\Http\JsonResponse;

class UpdatePreferencesController extends Controller
{
    public function __invoke(UpdatePreferencesRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $preferences = $user->preferences ?? [];
        $preferences[$data['key']] = $data['value'];
        $user->update(['preferences' => $preferences]);

        return response()->json(['status' => 'ok']);
    }
}
