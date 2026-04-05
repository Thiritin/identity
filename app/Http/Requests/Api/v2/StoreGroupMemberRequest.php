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
            'email' => ['required_without_all:user_id,username', 'nullable', 'email', 'exists:users,email'],
            'user_id' => ['required_without_all:email,username', 'nullable', 'string'],
            'username' => ['required_without_all:email,user_id', 'nullable', 'string', 'exists:users,name'],
            'level' => ['sometimes', 'string', Rule::enum(GroupUserLevel::class)],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'allow_making_staff' => ['sometimes', 'boolean'],
        ];
    }
}
