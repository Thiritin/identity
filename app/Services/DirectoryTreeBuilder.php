<?php

namespace App\Services;

use App\Enums\GroupTypeEnum;
use App\Models\Group;
use Illuminate\Support\Collection;

class DirectoryTreeBuilder
{
    public function build(): array
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

        return $this->buildTree($groups, null);
    }

    private function buildTree(Collection $groups, ?int $parentId): array
    {
        return $groups->where('parent_id', $parentId)
            ->map(fn (Group $group) => [
                'hashid' => $group->hashid,
                'name' => $group->name,
                'type' => $group->type->value,
                'member_count' => $group->users_count,
                'children' => $this->buildTree($groups, $group->id),
            ])
            ->values()
            ->all();
    }
}
