<?php

namespace App\Http\Requests\Api\v2;

use Illuminate\Foundation\Http\FormRequest;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:64'],
            'user_id' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'html' => ['nullable', 'string', 'max:65535'],
            'cta' => ['nullable', 'array', 'required_array_keys:label,url'],
            'cta.label' => ['required_with:cta', 'string', 'max:255'],
            'cta.url' => ['required_with:cta', 'url', 'max:2048'],
        ];
    }
}
