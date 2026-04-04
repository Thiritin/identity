<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Models\Convention;
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
                'slug' => $g->slug,
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

        $conventionAttendance = $user->conventions()
            ->orderByDesc('year')
            ->get()
            ->map(fn ($convention) => [
                'id' => $convention->id,
                'name' => $convention->name,
                'year' => $convention->year,
                'is_attended' => (bool) $convention->pivot->is_attended,
                'is_staff' => (bool) $convention->pivot->is_staff,
            ]);

        $canManageAttendance = $this->canManageUser($viewer, $user);
        $allConventions = $canManageAttendance
            ? Convention::query()->orderBy('year')->get(['id', 'name', 'year'])
            : null;

        return Inertia::render('Directory/StaffProfile', [
            'profileUser' => [
                'hashid' => $user->hashid,
                'name' => $user->name,
                'avatar' => $user->profile_photo_path
                    ? Storage::disk('s3-avatars')->url($user->profile_photo_path)
                    : null,
                'spoken_languages' => $user->spoken_languages,
                'credit_as' => $user->credit_as,
            ],
            'groups' => $groups,
            'visibleFields' => $visibleFields,
            'conventionAttendance' => $conventionAttendance,
            'allConventions' => $allConventions,
            'canManageAttendance' => $canManageAttendance,
            'nda' => [
                'verified_at' => $user->nda_verified_at?->toIso8601String(),
                'can_manage' => $viewer->hasStaffLevel([
                    GroupUserLevel::Director,
                    GroupUserLevel::DivisionDirector,
                ]),
            ],
        ]);
    }

    private function canManageUser(User $viewer, User $target): bool
    {
        $targetGroupIds = $target->groups()
            ->whereIn('groups.type', [
                GroupTypeEnum::Department->value,
                GroupTypeEnum::Division->value,
                GroupTypeEnum::Team->value,
            ])
            ->pluck('groups.id');

        if ($targetGroupIds->isEmpty()) {
            return false;
        }

        return $viewer->groups()
            ->whereIn('groups.id', $targetGroupIds)
            ->get()
            ->contains(fn ($group) => $group->pivot->canManageMembers());
    }
}
