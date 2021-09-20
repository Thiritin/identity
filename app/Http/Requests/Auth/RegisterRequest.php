<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

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
                "string",
                "unique:users,name"
            ],
            "email" => [
                "email",
                "required",
                "unique:users,email"
            ],
            'password' => [
                'required',
                'confirmed',
                \Illuminate\Validation\Rules\Password::min(8)->uncompromised()->mixedCase()->numbers()
            ],
        ];
    }
}
