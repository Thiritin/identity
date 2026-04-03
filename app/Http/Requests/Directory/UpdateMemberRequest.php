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
            'level' => ['required', Rule::enum(GroupUserLevel::class)],
            'title' => 'nullable|string|max:255',
            'can_manage_members' => 'boolean',
        ];
    }
}
