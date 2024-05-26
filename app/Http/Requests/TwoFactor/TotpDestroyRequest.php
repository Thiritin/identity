<?php

namespace App\Http\Requests\TwoFactor;

use Illuminate\Foundation\Http\FormRequest;

class TotpDestroyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => 'required|string',
        ];
    }
}
