<?php

namespace App\Http\Requests\Staff;

use App\Http\Controllers\Profile\Settings\AppsController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isFirstParty = (bool) $this->input('first_party', false);

        // Non-staff users cannot create first-party apps
        if (! $this->user()->isStaff()) {
            $isFirstParty = false;
        }

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
            'first_party' => ['nullable', 'boolean'],
            'description' => [$isFirstParty ? 'nullable' : 'required', 'string', 'max:1000'],
            'app_url' => [$isFirstParty ? 'nullable' : 'required', 'url', 'max:2000'],
            'developer_name' => [$isFirstParty ? 'nullable' : 'required', 'string', 'max:255'],
            'privacy_policy_url' => [$isFirstParty ? 'nullable' : 'required', 'url', 'max:2000'],
            'terms_of_service_url' => [$isFirstParty ? 'nullable' : 'required', 'url', 'max:2000'],
        ];

        return $rules;
    }
}
