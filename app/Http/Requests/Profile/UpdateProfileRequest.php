<?php

namespace App\Http\Requests\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'min:3',
                'max:25',
                'required',
                'alpha_dash',
                'unique:users,name,' . Auth::id(),
            ],
            'email' => [
                'email',
                'required',
                'unique:users,email,' . Auth::id(),
            ],
        ];
    }
}
