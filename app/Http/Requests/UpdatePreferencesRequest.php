<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    private const ALLOWED_KEYS = [
        'nsfw_content',
    ];

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', Rule::in(self::ALLOWED_KEYS)],
            'value' => ['required', 'boolean'],
        ];
    }
}
