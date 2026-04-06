<?php

namespace App\Http\Requests\Directory;

use App\Enums\GroupUserLevel;
use App\Support\Directory\DirectoryAuthorizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(DirectoryAuthorizer::class)
            ->canManageMembers($this->user(), $this->route('group'));
    }

    public function rules(): array
    {
        $group = $this->route('group');
        $assignable = app(DirectoryAuthorizer::class)
            ->assignableLevels($this->user(), $group);

        return [
            'user_hashid' => 'required|string|exists:users,hashid',
            'level' => ['required', Rule::in(array_map(fn ($l) => $l->value, $assignable))],
            'title' => 'nullable|string|max:255',
            'can_manage_members' => 'boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Strip can_manage_members for division groups
        if ($this->route('group')?->type->allowedLevels() === [\App\Enums\GroupUserLevel::DivisionDirector]) {
            $this->merge(['can_manage_members' => false]);
        }
    }
}
