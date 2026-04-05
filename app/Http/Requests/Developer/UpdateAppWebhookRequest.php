<?php

namespace App\Http\Requests\Developer;

use App\Models\App;
use App\Services\Webhooks\UserFieldMap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var App $app */
        $app = $this->route('app');
        return $this->user()->can('manageWebhooks', $app);
    }

    public function rules(): array
    {
        return [
            'webhook_url' => ['nullable', 'url', 'max:2000'],
            'webhook_event_name' => ['nullable', 'string', 'max:64'],
            'webhook_subscribed_fields' => ['nullable', 'array'],
            'webhook_subscribed_fields.*' => ['required', 'string', Rule::in(UserFieldMap::subscribableFields())],
        ];
    }
}
