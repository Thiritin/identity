<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
            'login_challenge' => 'required|alpha_num',
            'remember' => 'required|bool'
        ];
    }
}
