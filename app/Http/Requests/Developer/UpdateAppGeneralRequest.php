<?php

namespace App\Http\Requests\Developer;

use App\Models\App;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAppGeneralRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var App $app */
        $app = $this->route('app');
        return $this->user()->can('update', $app);
    }

    public function rules(): array
    {
        /** @var App $app */
        $app = $this->route('app');
        $isFirstParty = $app->isFirstParty();

        return array_merge([
            'client_name' => ['required', 'string', 'max:255'],
            'description' => [$isFirstParty ? 'nullable' : 'required', 'string', 'max:1000'],
            'app_url' => [$isFirstParty ? 'nullable' : 'required', 'url', 'max:2000'],
            'icon' => ['nullable', 'image', 'max:2048'],
        ], $isFirstParty ? [] : [
            'developer_name' => ['required', 'string', 'max:255'],
            'privacy_policy_url' => ['required', 'url', 'max:2000'],
            'terms_of_service_url' => ['required', 'url', 'max:2000'],
        ]);
    }
}
