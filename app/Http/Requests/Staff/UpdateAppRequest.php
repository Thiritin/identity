<?php

namespace App\Http\Requests\Staff;

use App\Http\Controllers\Profile\Settings\AppsController;
use App\Models\App;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var App $app */
        $app = $this->route('app');
        $isFirstParty = $app->isFirstParty();

        $rules = [
            'client_name' => ['required', 'string', 'max:255'],
            'redirect_uris' => ['required', 'array', 'min:1'],
            'redirect_uris.*' => ['required', 'url', 'max:2000'],
            'post_logout_redirect_uris' => ['nullable', 'array'],
            'post_logout_redirect_uris.*' => ['required', 'url', 'max:2000'],
            'frontchannel_logout_uri' => ['nullable', 'url', 'max:2000'],
            'backchannel_logout_uri' => ['nullable', 'url', 'max:2000'],
            'scope' => ['nullable', 'array'],
            'scope.*' => ['required', 'string', Rule::notIn(AppsController::RESTRICTED_SCOPES)],
            'description' => [$isFirstParty ? 'nullable' : 'required', 'string', 'max:1000'],
            'app_url' => [$isFirstParty ? 'nullable' : 'required', 'url', 'max:2000'],
        ];

        if (! $isFirstParty) {
            $rules['developer_name'] = ['required', 'string', 'max:255'];
            $rules['privacy_policy_url'] = ['required', 'url', 'max:2000'];
            $rules['terms_of_service_url'] = ['required', 'url', 'max:2000'];
        }

        return $rules;
    }
}
