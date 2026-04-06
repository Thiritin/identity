<?php

namespace App\Http\Requests;

use App\Enums\GroupTypeEnum;
use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => [
                'required',
                Rule::in([
                    GroupTypeEnum::Default->value,
                    GroupTypeEnum::Division->value,
                    GroupTypeEnum::Department->value,
                    GroupTypeEnum::Team->value,
                ]),
            ],
            'name' => 'string|required|max:255',
            'description' => 'string|nullable',
            'logo' => 'string|nullable',
            'parent_id' => [
                'nullable',
                'string',
                Rule::requiredIf(fn () => in_array(
                    $this->input('type'),
                    [GroupTypeEnum::Department->value, GroupTypeEnum::Team->value]
                )),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $parentId = $this->input('parent_id');
            $type = $this->input('type');

            if ($parentId === null) {
                return;
            }

            $parent = Group::findByHashid($parentId);
            if (! $parent) {
                $validator->errors()->add('parent_id', 'The selected parent group does not exist.');
                return;
            }

            // Validate parent type matches expected hierarchy
            if ($type === GroupTypeEnum::Department->value && $parent->type !== GroupTypeEnum::Division) {
                $validator->errors()->add('parent_id', 'A department must be created under a division.');
            }

            if ($type === GroupTypeEnum::Team->value && $parent->type !== GroupTypeEnum::Department) {
                $validator->errors()->add('parent_id', 'A team must be created under a department.');
            }
        });
    }

    public function resolvedParent(): ?Group
    {
        $parentId = $this->validated('parent_id');
        return $parentId ? Group::findByHashid($parentId) : null;
    }
}
