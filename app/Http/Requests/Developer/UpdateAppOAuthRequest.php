<?php

namespace App\Http\Requests\Developer;

use App\Http\Controllers\Profile\Settings\AppsController;
use App\Models\App;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppOAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var App $app */
        $app = $this->route('app');
        return $this->user()->can('update', $app);
    }

    public function rules(): array
    {
        return [
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'url', 'max:2000'],
            'scope' => ['nullable', 'array'],
            'scope.*' => ['required', 'string', Rule::notIn(AppsController::RESTRICTED_SCOPES)],
        ];
    }
}
