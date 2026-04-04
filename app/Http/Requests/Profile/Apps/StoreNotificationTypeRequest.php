<?php

namespace App\Http\Requests\Profile\Apps;

use App\Enums\NotificationCategory;
use App\Models\App;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreNotificationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $app = $this->route('app');

        return $app instanceof App
            && $app->user_id === $this->user()->id
            && (bool) $app->allow_notifications;
    }

    public function rules(): array
    {
        /** @var App $app */
        $app = $this->route('app');
        $appId = $app->id;

        return [
            'key' => [
                'required',
                'string',
                'regex:/^[a-z][a-z0-9_]{1,63}$/',
                Rule::unique('notification_types', 'key')->where('app_id', $appId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', Rule::enum(NotificationCategory::class)],
            'default_channels' => ['required', 'array', 'min:1'],
            'default_channels.*' => ['string', 'in:email,telegram,database'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $cat = $this->input('category');
            $channels = $this->input('default_channels', []);

            if ($cat === NotificationCategory::Transactional->value && ! in_array('email', $channels, true)) {
                $v->errors()->add('default_channels', 'Transactional notifications must include the email channel.');
            }
        });
    }
}
