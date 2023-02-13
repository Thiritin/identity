<?php

namespace App\Http\Requests\Profile;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            /*
            "name" => [
                "min:3",
                "max:39",
                "required",
                "string",
                "unique:users,name," . Auth::id(),
            ],*/
            "email" => [
                "email",
                "required",
                "unique:users,email," . Auth::id(),
            ],
        ];
    }
}
