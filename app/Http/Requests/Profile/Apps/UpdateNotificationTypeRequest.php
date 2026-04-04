<?php

namespace App\Http\Requests\Profile\Apps;

use App\Models\App;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationTypeRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_channels' => ['required', 'array', 'min:1'],
            'default_channels.*' => ['string', 'in:email,telegram,database'],
        ];
    }
}
