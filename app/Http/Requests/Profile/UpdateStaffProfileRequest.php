<?php

namespace App\Http\Requests\Profile;

use App\Enums\StaffProfileVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Locale;
use ResourceBundle;

class UpdateStaffProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    private const VISIBILITY_FIELDS = [
        'firstname',
        'lastname',
        'pronouns',
        'birthdate',
        'phone',
        'address',
        'emergency_contact',
    ];

    public function rules(): array
    {
        return [
            'firstname' => ['nullable', 'string', 'max:100'],
            'lastname' => ['nullable', 'string', 'max:100'],
            'pronouns' => ['nullable', 'string', 'max:50'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'size:2'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_telegram' => ['nullable', 'string', 'max:100'],
            'spoken_languages' => ['nullable', 'array'],
            'spoken_languages.*' => ['string', Rule::in($this->validLanguageCodes())],
            'credit_as' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', 'array'],
            'visibility.*' => [Rule::enum(StaffProfileVisibility::class)],
        ];
    }

    /** @return list<string> */
    private function validLanguageCodes(): array
    {
        return collect(ResourceBundle::getLocales(''))
            ->filter(fn (string $loc) => strlen($loc) === 2)
            ->filter(fn (string $loc) => Locale::getDisplayLanguage($loc, $loc) !== $loc)
            ->values()
            ->all();
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        if (isset($data['visibility'])) {
            $data['visibility'] = collect($data['visibility'])
                ->only(self::VISIBILITY_FIELDS)
                ->toArray();
        }

        return $data;
    }
}
