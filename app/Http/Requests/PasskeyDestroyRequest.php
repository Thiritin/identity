<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasskeyDestroyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => 'required|string',
            'keyId' => 'required|integer|exists:two_factors,id',
        ];
    }
}
