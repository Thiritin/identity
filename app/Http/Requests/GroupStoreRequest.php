<?php

namespace App\Http\Requests;

use App\Enums\GroupTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "type" => [Rule::enum(GroupTypeEnum::class)],
            "name" => "string|required|max:255",
            "description" => "string|nullable",
            "logo" => "string|nullable",
        ];
    }
}
