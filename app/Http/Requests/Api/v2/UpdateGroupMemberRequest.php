<?php

namespace App\Http\Requests\Api\v2;

use App\Enums\GroupUserLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level' => ['sometimes', 'string', Rule::enum(GroupUserLevel::class)],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
