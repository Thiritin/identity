<?php

namespace App\Http\Requests\Api\v2;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'firstname' => ['sometimes', 'string', 'max:255'],
            'lastname' => ['sometimes', 'string', 'max:255'],
            'birthdate' => ['sometimes', 'nullable', 'date'],
            'pronouns' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credit_as' => ['sometimes', 'nullable', 'string', 'max:255'],
            'spoken_languages' => ['sometimes', 'array'],
            'spoken_languages.*' => ['string', 'max:255'],
            'skills' => ['sometimes', 'array'],
            'skills.*' => ['string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telegram_username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'postal_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'nullable', 'string', 'max:2'],
            'emergency_contact' => ['sometimes', 'array'],
            'emergency_contact.name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'emergency_contact.phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'emergency_contact.telegram' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
