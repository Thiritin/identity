<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePreferencesRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class UpdatePreferencesController extends Controller
{
    public function __invoke(UpdatePreferencesRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $preferences = $user->preferences ?? [];
        $preferences[$data['key']] = $data['value'];
        $user->update(['preferences' => $preferences]);

        return Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('preferences_saved'),
        ])->back();
    }
}
