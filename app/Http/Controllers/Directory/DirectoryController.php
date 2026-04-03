<?php

namespace App\Http\Controllers\Directory;

use App\Enums\GroupUserLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Directory\UpdateGroupRequest;
use App\Models\Group;
use App\Services\DirectoryTreeBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DirectoryController extends Controller
{
    public function __construct(private DirectoryTreeBuilder $treeBuilder) {}

    public function index(): Response
    {
        $tree = $this->treeBuilder->build();
        $rootHashid = $tree[0]['hashid'] ?? null;

        return Inertia::render('Directory/DirectoryIndex', [
            'tree' => $tree,
            'rootHashid' => $rootHashid,
        ]);
    }

    public function show(Group $group): Response
    {
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
                'name' => $child->name,
                'type' => $child->type->value,
                'member_count' => $child->users_count,
            ]);

        $leaders = $members->filter(fn ($m) => in_array(
            $m['level'] instanceof GroupUserLevel ? $m['level']->value : $m['level'],
            ['division_director', 'director', 'team_lead']
        ));

        return Inertia::render('Directory/DirectoryShow', [
            'tree' => $this->treeBuilder->build(),
            'group' => [
                'hashid' => $group->hashid,
                'name' => $group->name,
                'type' => $group->type->value,
                'description' => $group->description,
                'logo_url' => $group->logo_url,
            ],
            'leaders' => $leaders->values(),
            'members' => $members->values(),
            'subGroups' => $subGroups,
            'canEdit' => request()->user()->can('update', $group),
        ]);
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

        $parentHashid = $group->parent?->hashid;
        $group->users()->detach();
        $group->delete();

        return redirect()->route('directory.show', $parentHashid);
    }
}
