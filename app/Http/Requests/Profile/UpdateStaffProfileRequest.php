<?php

namespace App\Http\Requests\Profile;

use App\Enums\StaffProfileVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    private const VISIBILITY_FIELDS = ['firstname', 'lastname', 'birthdate', 'phone'];

    public function rules(): array
    {
        return [
            'firstname' => ['nullable', 'string', 'max:100'],
            'lastname' => ['nullable', 'string', 'max:100'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:50'],
            'spoken_languages' => ['nullable', 'array'],
            'spoken_languages.*' => ['string', 'max:5'],
            'credit_as' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', 'array'],
            'visibility.*' => [Rule::enum(StaffProfileVisibility::class)],
        ];
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
