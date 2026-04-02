<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupCreditAsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    public function rules(): array
    {
        $userGroupIds = $this->user()->groups()->pluck('groups.id')->toArray();

        return [
            'groups' => ['required', 'array'],
            'groups.*.group_id' => ['required', 'integer', 'in:' . implode(',', $userGroupIds)],
            'groups.*.credit_as' => ['nullable', 'string', 'max:100'],
        ];
    }
}
