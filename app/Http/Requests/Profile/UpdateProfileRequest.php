<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "name" => [
                "min:3",
                "max:25",
                "required",
                "string",
            ],
            "email" => [
                "email",
                "required",
            ]
        ];
    }
}
