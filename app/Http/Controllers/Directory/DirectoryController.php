<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupTypeEnum;
use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Directory\UpdateGroupRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DirectoryController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();

        $myGroupIds = $user->groups()->pluck('groups.id')->all();

        $myMemberships = $user->groups()
            ->whereIn('type', [
                GroupTypeEnum::Division,
                GroupTypeEnum::Department,
                GroupTypeEnum::Team,
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Group $group) => [
                'hashid' => $group->hashid,
                'slug' => $group->slug,
                'name' => $group->name,
                'type' => $group->type->value,
                'title' => $group->pivot->title,
                'level' => $group->pivot->level,
            ]);

        $divisions = Group::query()
            ->where('type', GroupTypeEnum::Division)
            ->with(['children' => fn ($q) => $q
                ->where('type', GroupTypeEnum::Department)
                ->withCount('users')
                ->orderBy('name'),
            ])
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Group $group) => [
                'hashid' => $group->hashid,
                'slug' => $group->slug,
                'name' => $group->name,
                'icon' => $group->icon,
                'member_count' => $group->users_count,
                'departments' => $group->children->map(fn (Group $dept) => [
                    'hashid' => $dept->hashid,
                    'slug' => $dept->slug,
                    'name' => $dept->name,
                    'icon' => $dept->icon,
                    'member_count' => $dept->users_count,
                    'is_mine' => in_array($dept->id, $myGroupIds),
                ])->values(),
            ]);

        $orphanDepartments = Group::query()
            ->where('type', GroupTypeEnum::Department)
            ->whereDoesntHave('parent', fn ($q) => $q->where('type', GroupTypeEnum::Division))
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Group $group) => [
                'hashid' => $group->hashid,
                'slug' => $group->slug,
                'name' => $group->name,
                'icon' => $group->icon,
                'member_count' => $group->users_count,
            ]);

        $systemMemberships = $user->groups()
            ->whereNotIn('type', [
                GroupTypeEnum::Division,
                GroupTypeEnum::Department,
                GroupTypeEnum::Team,
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Group $group) => [
                'hashid' => $group->hashid,
                'slug' => $group->slug,
                'name' => $group->name,
                'type' => $group->type->value,
            ]);

        return Inertia::render('Directory/DirectoryIndex', [
            'myMemberships' => $myMemberships,
            'divisions' => $divisions,
            'orphanDepartments' => $orphanDepartments,
            'systemMemberships' => $systemMemberships,
        ]);
    }

    public function show(string $slug): Response
    {
        $group = Group::where('slug', $slug)->firstOrFail();
        $this->authorize('view', $group);

        $members = $group->users()
            ->orderByRaw("CASE WHEN group_user.level IN ('division_director','director','team_lead') THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get()
            ->map(fn ($user) => [
                'hashid' => $user->hashid,
                'name' => $user->name,
                'avatar' => $user->profile_photo_path
                    ? Storage::disk('s3-avatars')->url($user->profile_photo_path)
                    : null,
                'level' => $user->pivot->level,
                'title' => $user->pivot->title,
                'can_manage_members' => $user->pivot->can_manage_members,
            ]);

        $subGroups = $group->children()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn ($child) => [
                'hashid' => $child->hashid,
                'slug' => $child->slug,
                'name' => $child->name,
                'icon' => $child->icon,
                'type' => $child->type->value,
                'member_count' => $child->users_count,
            ]);

        $leaders = $members->filter(fn ($m) => in_array(
            $m['level'] instanceof GroupUserLevel ? $m['level']->value : $m['level'],
            ['division_director', 'director', 'team_lead']
        ));

        return Inertia::render('Directory/DirectoryShow', [
            'directorySelectedSlug' => $group->slug,
            'group' => [
                'hashid' => $group->hashid,
                'slug' => $group->slug,
                'name' => $group->name,
                'type' => $group->type->value,
                'description' => $group->description,
                'icon' => $group->icon,
                'logo_url' => $group->logo_url,
            ],
            'leaders' => $leaders->values(),
            'members' => $members->values(),
            'subGroups' => $subGroups,
            'canEdit' => request()->user()->can('update', $group),
            'assignableLevels' => $this->getAssignableLevels(request()->user(), $group),
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

        // Check viewer's level in this group and parent group
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

        // Always include member
        $levels->push(GroupUserLevel::Member);

        return $levels->unique()->map(fn ($l) => $l->value)->values()->all();
    }

    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['logo'])) {
            $path = $data['logo']->store('avatars', 'public');
            $data['logo'] = basename($path);
        }

        $group->update($data);

        return back();
    }

    public function destroy(Group $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        $parentSlug = $group->parent?->slug;
        $group->users()->detach();
        $group->delete();

        return redirect()->route('directory.show', $parentSlug);
    }
}
