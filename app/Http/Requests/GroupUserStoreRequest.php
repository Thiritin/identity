<?php

namespace App\Http\Requests;

use App\Enums\GroupUserLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupUserStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "email" => "email|required",
            "level" => [
                Rule::enum(GroupUserLevel::class)
            ]
        ];
    }
}
