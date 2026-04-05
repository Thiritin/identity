<?php

namespace App\Http\Requests\Developer;

use App\Models\App;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppLogoutRequest extends FormRequest
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
            'post_logout_redirect_uris' => ['nullable', 'array'],
            'post_logout_redirect_uris.*' => ['required', 'url', 'max:2000'],
            'frontchannel_logout_uri' => ['nullable', 'url', 'max:2000'],
            'backchannel_logout_uri' => ['nullable', 'url', 'max:2000'],
        ];
    }
}
