<?php

namespace App\Http\Requests\Directory;

use App\Enums\GroupTypeEnum;
use App\Support\Directory\DirectoryAuthorizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
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
            'level' => ['required', Rule::in(array_map(fn ($l) => $l->value, $assignable))],
            'title' => 'nullable|string|max:255',
            'can_manage_members' => 'boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('group')?->type === GroupTypeEnum::Division) {
            $this->merge(['can_manage_members' => false]);
        }
    }
}
