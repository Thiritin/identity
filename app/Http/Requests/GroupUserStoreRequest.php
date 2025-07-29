<?php

namespace App\Http\Requests;

use App\Domains\Staff\Enums\GroupUserLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupUserStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'id' => 'required_without:email|prohibits:email',
            'email' => 'email|required_without:id|exists:users,email|prohibits:id',
            'level' => [
                Rule::enum(GroupUserLevel::class),
            ],
        ];
    }
}
