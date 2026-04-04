<?php

namespace App\Http\Requests\Profile;

use App\Enums\GroupTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserConventionAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $viewer = $this->user();
        $target = $this->route('user');

        return $this->canManageUser($viewer, $target);
    }

    public function rules(): array
    {
        $target = $this->route('user');

        $rules = [
            'action' => ['required', Rule::in(['add', 'update', 'remove'])],
            'convention_id' => ['required', 'exists:conventions,id'],
        ];

        if ($this->input('action') === 'add') {
            $rules['convention_id'][] = Rule::unique('convention_attendee')
                ->where('user_id', $target->id);
            $rules['is_attended'] = ['sometimes', 'boolean'];
            $rules['is_staff'] = ['sometimes', 'boolean'];
        }

        if ($this->input('action') === 'update') {
            $rules['is_attended'] = ['sometimes', 'boolean'];
            $rules['is_staff'] = ['sometimes', 'boolean'];
        }

        return $rules;
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
