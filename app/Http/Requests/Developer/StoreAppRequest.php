<?php

namespace App\Http\Requests\Developer;

use App\Http\Controllers\Profile\Settings\AppsController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\App::class);
    }

    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'redirect_uri' => ['required', 'url', 'max:2000'],
            'scope' => ['nullable', 'array'],
            'scope.*' => ['required', 'string', Rule::notIn(AppsController::RESTRICTED_SCOPES)],
        ];
    }
}
