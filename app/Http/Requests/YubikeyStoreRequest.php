<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class YubikeyStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => 'required|string',
            'name' => 'required|string|max:80',
        ];
    }
}
