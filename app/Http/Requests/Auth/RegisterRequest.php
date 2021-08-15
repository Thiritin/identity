<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules()
    {
        return [
            "username" => [
                "min:3",
                "max:25",
                "required",
                "string"
            ],
            "email" => [
                "email",
                "required",
                "unique:users,email"
            ],
        ];
    }
}
