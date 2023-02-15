<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * @var mixed
     */

    public function rules()
    {
        return [
            "username" => [
                "min:3",
                "max:25",
                "required",
                "alpha_dash",
                "unique:users,name",
            ],
            "email" => [
                "email",
                "required",
                "unique:users,email",
            ],
            'password' => [
                'required',
                'confirmed',
                'max:200',
                Password::min(10)->symbols()->mixedCase()->numbers(),
            ],
        ];
    }
}
