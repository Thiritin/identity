<?php

namespace App\Http\Requests\TwoFactor;

use Illuminate\Foundation\Http\FormRequest;

class TotpStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|numeric|digits:6',
            'secret' => 'required|string',
        ];
    }
}
