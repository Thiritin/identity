<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GroupUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name.*' => 'string|required|max:255',
            'description.*' => 'string|nullable',
            'logo' => 'file|image|nullable|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
