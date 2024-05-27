<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "current_password" => [
                "required",
                "current_password",
            ],
            "password" => [
                "required",
                "confirmed",
                \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }
}
