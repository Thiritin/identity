<?php

namespace App\Http\Requests\Profile;

use App\Enums\NotificationCategory;
use App\Models\NotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'channels' => ['required', 'array'],
            'channels.email' => ['required', 'boolean'],
            'channels.telegram' => ['required', 'boolean'],
            'channels.database' => ['required', 'boolean'],
            'types' => ['array'],
            'types.*' => ['array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $typeIds = array_keys($this->input('types', []));
            $types = NotificationType::whereIn('id', $typeIds)->get()->keyBy('id');

            foreach ($typeIds as $id) {
                $type = $types->get((int) $id);
                if ($type && $type->category === NotificationCategory::Transactional) {
                    $v->errors()->add("types.$id", 'Transactional notifications cannot be disabled.');
                }
            }
        });
    }
}
