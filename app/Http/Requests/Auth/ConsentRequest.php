<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ConsentRequest extends FormRequest
{
    public function rules()
    {
        return [
            'consent_challenge' => 'required',
        ];
    }
}
