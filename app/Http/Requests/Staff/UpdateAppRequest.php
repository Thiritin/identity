<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'url', 'max:2000'],
            'post_logout_redirect_uris' => ['nullable', 'array'],
            'post_logout_redirect_uris.*' => ['required', 'url', 'max:2000'],
            'scope' => ['nullable', 'array'],
            'scope.*' => ['required', 'string'],
        ];
    }
}
