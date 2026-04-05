<?php

namespace App\Http\Requests\Directory;

use App\Enums\GroupUserLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    public function rules(): array
    {
        return [
            'level' => ['required', Rule::enum(GroupUserLevel::class), function ($attribute, $value, $fail) {
                $this->validateLevelAssignment($value, $fail);
            }],
            'title' => 'nullable|string|max:255',
            'can_manage_members' => 'boolean',
        ];
    }

    private function validateLevelAssignment(string $value, \Closure $fail): void
    {
        $requestedLevel = GroupUserLevel::from($value);

        // Admins can assign any level
        if ($this->user()->is_admin) {
            return;
        }

        // Member can always be assigned by anyone who can manage
        if ($requestedLevel === GroupUserLevel::Member) {
            return;
        }

        $group = $this->route('group');
        $viewer = $this->user();

        // Check viewer's level in this group and parent group
        $viewerLevel = $this->getViewerHighestLevel($viewer, $group);

        if (! $viewerLevel) {
            $fail('You do not have permission to assign this level.');

            return;
        }

        $assignable = $viewerLevel->assignableLevels();

        if (! in_array($requestedLevel, $assignable, true)) {
            $fail('You do not have permission to assign this level.');
        }
    }

    private function getViewerHighestLevel($viewer, $group): ?GroupUserLevel
    {
        // Check viewer's level in this group
        $membership = $viewer->groups()->where('groups.id', $group->id)->first();

        if ($membership) {
            $level = $membership->pivot->level instanceof GroupUserLevel
                ? $membership->pivot->level
                : GroupUserLevel::from($membership->pivot->level);

            if ($level->isLeadRole()) {
                return $level;
            }
        }

        // Check viewer's level in parent group
        if ($group->parent_id) {
            $parentMembership = $viewer->groups()->where('groups.id', $group->parent_id)->first();

            if ($parentMembership) {
                $level = $parentMembership->pivot->level instanceof GroupUserLevel
                    ? $parentMembership->pivot->level
                    : GroupUserLevel::from($parentMembership->pivot->level);

                if ($level->isLeadRole()) {
                    return $level;
                }
            }
        }

        return null;
    }
}
