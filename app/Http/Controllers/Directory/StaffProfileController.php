<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Models\Convention;
use App\Models\Group;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StaffProfileController extends Controller
{
    public function show(string $slug, User $user): Response
    {
        $group = Group::where('slug', $slug)->firstOrFail();
        $this->authorize('view', $group);

        $viewer = request()->user();

        $breadcrumbs = $this->buildBreadcrumbs($group, $user);

        // Get user's membership in this specific group
        $membership = $user->groups()->where('groups.id', $group->id)->first();
        $groupMembership = $membership ? [
            'level' => $membership->pivot->level instanceof GroupUserLevel
                ? $membership->pivot->level->value
                : $membership->pivot->level,
            'title' => $membership->pivot->title,
            'can_manage_members' => (bool) $membership->pivot->can_manage_members,
        ] : null;

        // All groups the user belongs to (for the roles section)
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

        $visibleFields = collect(['firstname', 'lastname', 'pronouns', 'birthdate', 'phone', 'telegram'])
            ->filter(fn ($field) => $user->canViewStaffField($field, $viewer))
            ->mapWithKeys(fn ($field) => [
                $field => $user->getAttributeValue($field === 'telegram' ? 'telegram_username' : $field),
            ])
            ->all();

        $canEdit = $group->canManageMembers($viewer);

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

        $allConventions = $canEdit
            ? Convention::query()->orderBy('year')->get(['id', 'name', 'year'])
            : null;

        return Inertia::render('Directory/StaffProfile', [
            'directorySelectedSlug' => $group->slug,
            'breadcrumbs' => $breadcrumbs,
            'group' => [
                'hashid' => $group->hashid,
                'slug' => $group->slug,
                'name' => $group->name,
                'type' => $group->type->value,
            ],
            'groupMembership' => $groupMembership,
            'canEdit' => $canEdit,
            'assignableLevels' => $this->getAssignableLevels($viewer, $group),
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
        ]);
    }

    /**
     * @return string[]
     */
    private function getAssignableLevels(User $viewer, Group $group): array
    {
        if ($viewer->is_admin) {
            return array_map(fn ($l) => $l->value, GroupUserLevel::cases());
        }

        $levels = collect();

        $membership = $viewer->groups()->where('groups.id', $group->id)->first();
        if ($membership) {
            $level = $membership->pivot->level instanceof GroupUserLevel
                ? $membership->pivot->level
                : GroupUserLevel::from($membership->pivot->level);
            $levels = $levels->merge($level->assignableLevels());
        }

        if ($group->parent_id) {
            $parentMembership = $viewer->groups()->where('groups.id', $group->parent_id)->first();
            if ($parentMembership) {
                $level = $parentMembership->pivot->level instanceof GroupUserLevel
                    ? $parentMembership->pivot->level
                    : GroupUserLevel::from($parentMembership->pivot->level);
                $levels = $levels->merge($level->assignableLevels());
            }
        }

        $levels->push(GroupUserLevel::Member);

        return $levels->unique()->map(fn ($l) => $l->value)->values()->all();
    }

    /**
     * @return array<int, array{label: string, href: string|null}>
     */
    private function buildBreadcrumbs(Group $group, User $user): array
    {
        $crumbs = [
            ['label' => __('directory'), 'href' => route('directory.index')],
        ];

        // Walk up the ancestry chain (skip root)
        $ancestors = [];
        $current = $group;
        while ($current) {
            if ($current->type === GroupTypeEnum::Root) {
                break;
            }
            $ancestors[] = $current;
            $current = $current->parent;
        }

        $ancestors = array_reverse($ancestors);

        foreach ($ancestors as $ancestor) {
            $crumbs[] = [
                'label' => $ancestor->name,
                'href' => route('directory.show', $ancestor->slug),
            ];
        }

        // Final crumb: the user (no link)
        $crumbs[] = [
            'label' => $user->name,
            'href' => null,
        ];

        return $crumbs;
    }
}
