<?php

namespace App\Http\Requests\Api\v2;

use App\Enums\GroupUserLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required_without:user_id', 'nullable', 'email', 'exists:users,email'],
            'user_id' => ['required_without:email', 'nullable', 'string'],
            'level' => ['sometimes', 'string', Rule::enum(GroupUserLevel::class)],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
