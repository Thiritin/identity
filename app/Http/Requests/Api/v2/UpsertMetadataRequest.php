<?php

namespace App\Http\Requests\Api\v2;

use Illuminate\Foundation\Http\FormRequest;

class UpsertMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'value' => ['required', 'string', 'max:65535'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
