<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StaffProfileController extends Controller
{
    public function show(User $user): Response
    {
        $viewer = request()->user();

        $groups = $user->groups()
            ->whereIn('type', [GroupTypeEnum::Division, GroupTypeEnum::Department, GroupTypeEnum::Team])
            ->orderBy('name')
            ->get()
            ->map(fn ($g) => [
                'hashid' => $g->hashid,
                'name' => $g->name,
                'type' => $g->type->value,
                'title' => $g->pivot->title,
                'level' => $g->pivot->level instanceof GroupUserLevel
                    ? $g->pivot->level->value
                    : $g->pivot->level,
                'credit_as' => $g->pivot->credit_as,
            ]);

        $visibleFields = collect(['firstname', 'lastname', 'birthdate', 'phone', 'telegram'])
            ->filter(fn ($field) => $user->canViewStaffField($field, $viewer))
            ->mapWithKeys(fn ($field) => [
                $field => $user->getAttributeValue($field === 'telegram' ? 'telegram_username' : $field),
            ])
            ->all();

        return Inertia::render('Directory/StaffProfile', [
            'profileUser' => [
                'hashid' => $user->hashid,
                'name' => $user->name,
                'avatar' => $user->profile_photo_path
                    ? Storage::disk('s3-avatars')->url($user->profile_photo_path)
                    : null,
                'spoken_languages' => $user->spoken_languages,
                'first_eurofurence' => $user->first_eurofurence,
                'first_year_staff' => $user->first_year_staff,
                'credit_as' => $user->credit_as,
            ],
            'groups' => $groups,
            'visibleFields' => $visibleFields,
        ]);
    }
}
