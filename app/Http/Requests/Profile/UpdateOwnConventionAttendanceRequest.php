<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnConventionAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    public function rules(): array
    {
        $rules = [
            'action' => ['required', Rule::in(['add', 'update', 'remove'])],
            'convention_id' => ['required', 'exists:conventions,id'],
        ];

        if ($this->input('action') === 'add') {
            $rules['convention_id'][] = Rule::unique('convention_attendee')
                ->where('user_id', $this->user()->id);
        }

        if ($this->input('action') === 'update') {
            $rules['is_attended'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }
}
