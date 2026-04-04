<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    private const ALLOWED_KEYS = [
        'nsfw_content',
        'locale',
        'theme',
    ];

    private const SUPPORTED_LOCALES = ['en', 'de', 'fr'];

    private const SUPPORTED_THEMES = ['system', 'light', 'dark'];

    public function rules(): array
    {
        $key = $this->input('key');

        $valueRules = match ($key) {
            'locale' => ['required', 'string', Rule::in(self::SUPPORTED_LOCALES)],
            'theme' => ['required', 'string', Rule::in(self::SUPPORTED_THEMES)],
            default => ['required', 'boolean'],
        };

        return [
            'key' => ['required', 'string', Rule::in(self::ALLOWED_KEYS)],
            'value' => $valueRules,
        ];
    }
}
