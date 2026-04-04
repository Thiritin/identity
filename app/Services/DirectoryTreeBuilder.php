<?php

namespace App\Services;

use App\Enums\GroupTypeEnum;
use App\Models\Group;
use Illuminate\Support\Collection;

class DirectoryTreeBuilder
{
    public function build(array $myGroupIds = []): array
    {
        $groups = Group::query()
            ->whereIn('type', [
                GroupTypeEnum::Root,
                GroupTypeEnum::Division,
                GroupTypeEnum::Department,
                GroupTypeEnum::Team,
            ])
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $root = $groups->firstWhere('type', GroupTypeEnum::Root);

        $rootId = $root?->id;

        return $this->buildTree($groups, $rootId, $myGroupIds);
    }

    private function buildTree(Collection $groups, ?int $parentId, array $myGroupIds): array
    {
        return $groups->where('parent_id', $parentId)
            ->map(function (Group $group) use ($groups, $myGroupIds) {
                $children = $this->buildTree($groups, $group->id, $myGroupIds);

                return [
                    'hashid' => $group->hashid,
                    'slug' => $group->slug,
                    'name' => $group->name,
                    'icon' => $group->icon,
                    'type' => $group->type->value,
                    'member_count' => $group->type === GroupTypeEnum::Division
                        ? count($children)
                        : $group->users_count,
                    'is_mine' => in_array($group->id, $myGroupIds),
                    'children' => $children,
                ];
            })
            ->values()
            ->all();
    }
}
