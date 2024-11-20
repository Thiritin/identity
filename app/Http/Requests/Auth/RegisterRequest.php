<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules()
    {
        return [
            'username' => [
                'min:3',
                'max:25',
                'required',
                'alpha_dash',
                'unique:users,name',
            ],
            'email' => [
                'email',
                'required',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'confirmed',
                'max:255',
                \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers(),
            ],
        ];
    }
}
